<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MdChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->setLocale('ja');
        config(['app.locale' => 'ja']);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'tester',
            'email' => 'tester@example.com',
            'password' => 'Passw0rd!',
        ], $overrides));
    }

    private function validPassword(): string
    {
        return 'Passw0rd!';
    }

    // --- dsc_01make-regist.md ---

    public function test_register_success_shows_green_message_on_login(): void
    {
        $this->post('/regist', [
            'name' => '山田太郎',
            'email' => 'New.User@example.com',
            'password' => $this->validPassword(),
        ])
            ->assertRedirect('/')
            ->assertSessionHas('message', '会員登録が完了しました。ログインしてください。');

        $this->assertDatabaseHas('users', [
            'name' => '山田太郎',
            'email' => 'new.user@example.com',
        ]);

        $this->followingRedirects()
            ->post('/regist', [
                'name' => '次郎です',
                'email' => 'jiro@example.com',
                'password' => $this->validPassword(),
            ])
            ->assertOk()
            ->assertSee('会員登録が完了しました。ログインしてください。');
    }

    public function test_register_validation_messages(): void
    {
        $this->from('/regist')->post('/regist', [
            'name' => '',
            'email' => '',
            'password' => '',
        ])
            ->assertRedirect('/regist')
            ->assertSessionHasErrors([
                'name' => 'ユーザー名を入力してください。',
                'email' => 'メールアドレスを入力してください。',
                'password' => 'パスワードを入力してください。',
            ]);

        $this->from('/regist')->post('/regist', [
            'name' => 'user@name',
            'email' => 'not-an-email',
            'password' => 'あいうえおかきく',
        ])
            ->assertSessionHasErrors([
                'name' => '使用できない文字が含まれています。',
                'email' => 'メールアドレスの形式が正しくありません。',
                'password' => 'パスワードは半角英数字と記号で入力してください。',
            ])
            ->assertSessionHasInput('name', 'user@name');

        $this->from('/regist')->post('/regist', [
            'name' => 'ab',
            'email' => 'ok@example.com',
            'password' => $this->validPassword(),
        ])->assertSessionHasErrors([
            'name' => 'ユーザー名は3文字以上16文字以内で入力してください。',
        ]);

        $this->from('/regist')->post('/regist', [
            'name' => str_repeat('a', 17),
            'email' => 'ok@example.com',
            'password' => $this->validPassword(),
        ])->assertSessionHasErrors([
            'name' => 'ユーザー名は3文字以上16文字以内で入力してください。',
        ]);
    }

    public function test_register_duplicate_email_is_case_insensitive(): void
    {
        $this->makeUser(['email' => 'dup@example.com']);

        $this->from('/regist')->post('/regist', [
            'name' => '別ユーザー',
            'email' => 'DUP@example.com',
            'password' => $this->validPassword(),
        ])->assertSessionHasErrors([
            'email' => 'そのメールアドレスはすでに使用されています。',
        ]);
    }

    // --- dsc_02-1make-login.md ---

    public function test_registered_user_can_login_to_dashboard(): void
    {
        $this->makeUser();

        $this->post('/', [
            'email' => 'tester@example.com',
            'password' => $this->validPassword(),
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
    }

    public function test_wrong_password_shows_login_error(): void
    {
        $this->makeUser();

        $this->from('/')->post('/', [
            'email' => 'tester@example.com',
            'password' => 'WrongPass1!',
        ])->assertSessionHasErrors([
            'email' => 'ログイン情報が正しくありません。',
        ]);
    }

    public function test_guest_dashboard_redirects_to_login_with_message(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'ログインしてください');

        $this->followingRedirects()
            ->get('/dashboard')
            ->assertSee('ログインしてください');
    }

    public function test_login_form_posts_to_login_route(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('method="post"', $html);
        $this->assertTrue(
            str_contains($html, 'action="'.route('login').'"')
            || str_contains($html, 'action="'.url('/').'"')
            || str_contains($html, 'action="/"'),
            'ログインフォームの送信先が /（route login）であること'
        );
    }

    // --- dsc_02-2make-logout.md ---

    public function test_logout_returns_to_login_and_blocks_dashboard(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->get('/dashboard')->assertRedirect(route('login'));

        $this->post('/', [
            'email' => 'tester@example.com',
            'password' => $this->validPassword(),
        ])->assertRedirect('/dashboard');
    }

    public function test_guest_post_logout_goes_to_login(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_logout_is_post_not_get(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('logout'))
            ->assertStatus(405);
    }

    // --- dsc_03edit-accout-username.md ---

    public function test_username_success_cases(): void
    {
        $user = $this->makeUser();

        foreach (['user123', 'abc', '1234567890123456'] as $name) {
            $this->actingAs($user)
                ->from('/edit-username')
                ->post(route('update-username'), ['name' => $name])
                ->assertRedirect(route('account'));

            $this->assertSame($name, $user->fresh()->name);

            $this->actingAs($user)
                ->get('/account')
                ->assertOk()
                ->assertSee($name, false);
        }
    }

    public function test_username_two_kanji_fails_min_three(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->from('/edit-username')
            ->post(route('update-username'), ['name' => '太郎'])
            ->assertSessionHasErrors(['name' => 'ユーザー名は3文字以上です。']);
    }

    public function test_username_cancel_does_not_update(): void
    {
        $user = $this->makeUser(['name' => '元の名前']);

        $this->actingAs($user)
            ->get('/edit-username')
            ->assertOk()
            ->assertSee(route('account'), false);

        $this->actingAs($user)
            ->get('/account')
            ->assertOk();

        $this->assertSame('元の名前', $user->fresh()->name);
    }

    public function test_username_validation_and_old_input(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/edit-username')->post(route('update-username'), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'ユーザー名は必須です。']);

        $this->actingAs($user)->from('/edit-username')->post(route('update-username'), ['name' => 'ab'])
            ->assertSessionHasErrors(['name' => 'ユーザー名は3文字以上です。']);

        $this->actingAs($user)->from('/edit-username')->post(route('update-username'), ['name' => str_repeat('a', 17)])
            ->assertSessionHasErrors(['name' => 'ユーザー名は16文字以内です。']);

        $this->actingAs($user)->from('/edit-username')->post(route('update-username'), ['name' => 'user@name'])
            ->assertSessionHasErrors('name')
            ->assertSessionHasInput('name', 'user@name');

        $this->assertStringContainsString(
            '半角英数字、ひらがな、カタカナ、漢字',
            session('errors')->first('name')
        );
    }

    public function test_username_guest_cannot_open_or_post(): void
    {
        $user = $this->makeUser(['name' => '元の名前']);

        $this->get('/edit-username')->assertRedirect(route('login'));

        $this->post(route('update-username'), ['name' => '太郎'])
            ->assertRedirect(route('login'));

        $this->assertSame('元の名前', $user->fresh()->name);
    }

    // --- dsc_04edit-email.md ---

    public function test_email_update_new_unused_and_self(): void
    {
        $user = $this->makeUser(['email' => 'me@example.com']);

        $this->actingAs($user)
            ->post(route('update-email'), ['email' => 'fresh@example.com'])
            ->assertRedirect(route('account'));
        $this->assertSame('fresh@example.com', $user->fresh()->email);

        $this->actingAs($user)
            ->get('/account')
            ->assertSee('fresh@example.com');

        $this->actingAs($user)
            ->post(route('update-email'), ['email' => 'fresh@example.com'])
            ->assertRedirect(route('account'));
    }

    public function test_email_saved_as_lowercase(): void
    {
        $user = $this->makeUser(['email' => 'me@example.com']);

        $this->actingAs($user)
            ->post(route('update-email'), ['email' => 'User@example.com'])
            ->assertRedirect(route('account'));

        $this->assertSame('user@example.com', $user->fresh()->email);
        $this->actingAs($user)->get('/account')->assertSee('user@example.com');
    }

    public function test_email_cancel_does_not_update(): void
    {
        $user = $this->makeUser(['email' => 'keep@example.com']);

        $this->actingAs($user)
            ->get('/edit-email')
            ->assertOk()
            ->assertSee(route('account'), false);

        $this->assertSame('keep@example.com', $user->fresh()->email);
    }

    public function test_email_validation_duplicate_and_old_input(): void
    {
        $other = $this->makeUser(['email' => 'taken@example.com']);
        $user = $this->makeUser(['name' => '自分', 'email' => 'me@example.com']);

        $this->actingAs($user)->from('/edit-email')->post(route('update-email'), ['email' => ''])
            ->assertSessionHasErrors(['email' => 'メールアドレスは必須です。']);

        $this->actingAs($user)->from('/edit-email')->post(route('update-email'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['email' => 'メールアドレスの形式が不正です。'])
            ->assertSessionHasInput('email', 'not-an-email');

        $this->actingAs($user)->from('/edit-email')->post(route('update-email'), [
            'email' => str_repeat('a', 244).'@example.com',
        ])->assertSessionHasErrors(['email' => 'メールアドレスは255文字以内です。']);

        $this->actingAs($user)->from('/edit-email')->post(route('update-email'), ['email' => 'taken@example.com'])
            ->assertSessionHasErrors(['email' => 'そのメールアドレスはすでに使用されています。']);

        $this->actingAs($user)->from('/edit-email')->post(route('update-email'), ['email' => 'TAKEN@example.com'])
            ->assertSessionHasErrors(['email' => 'そのメールアドレスはすでに使用されています。']);

        $this->assertSame('taken@example.com', $other->fresh()->email);
    }

    public function test_email_guest_cannot_open_or_post(): void
    {
        $user = $this->makeUser(['email' => 'keep@example.com']);

        $this->get('/edit-email')->assertRedirect(route('login'));
        $this->post(route('update-email'), ['email' => 'hack@example.com'])
            ->assertRedirect(route('login'));
        $this->assertSame('keep@example.com', $user->fresh()->email);
    }

    // --- dsc_05edit-password.md ---

    public function test_password_change_success_and_old_password_fails(): void
    {
        $user = $this->makeUser();
        $new = 'NewPass1!';

        $this->actingAs($user)
            ->post(route('update-password'), [
                'current_password' => $this->validPassword(),
                'new_password' => $new,
                'new_password_confirmation' => $new,
            ])
            ->assertRedirect(route('account'));

        $this->assertTrue(Hash::check($new, $user->fresh()->password));
        $this->assertFalse(Hash::check($this->validPassword(), $user->fresh()->password));

        $this->post(route('logout'));

        $this->post('/', [
            'email' => 'tester@example.com',
            'password' => $new,
        ])->assertRedirect('/dashboard');

        $this->post(route('logout'));

        $this->from('/')->post('/', [
            'email' => 'tester@example.com',
            'password' => $this->validPassword(),
        ])->assertSessionHasErrors('email');
    }

    public function test_password_cancel_does_not_update(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/edit-password')
            ->assertOk()
            ->assertSee(route('account'), false);

        $this->assertTrue(Hash::check($this->validPassword(), $user->fresh()->password));
    }

    public function test_password_validation_messages(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->from('/edit-password')->post(route('update-password'), [
            'current_password' => '',
            'new_password' => $this->validPassword(),
            'new_password_confirmation' => $this->validPassword(),
        ])->assertSessionHasErrors(['current_password' => '現在のパスワードを入力してください。']);

        $this->actingAs($user)->from('/edit-password')->post(route('update-password'), [
            'current_password' => 'WrongPass1!',
            'new_password' => $this->validPassword(),
            'new_password_confirmation' => $this->validPassword(),
        ])->assertSessionHasErrors(['current_password' => '現在のパスワードが正しくありません。']);

        $this->actingAs($user)->from('/edit-password')->post(route('update-password'), [
            'current_password' => $this->validPassword(),
            'new_password' => '',
            'new_password_confirmation' => $this->validPassword(),
        ])->assertSessionHasErrors(['new_password' => 'パスワードを入力してください。']);

        $this->actingAs($user)->from('/edit-password')->post(route('update-password'), [
            'current_password' => $this->validPassword(),
            'new_password' => 'あいうえおかきく',
            'new_password_confirmation' => 'あいうえおかきく',
        ])->assertSessionHasErrors(['new_password' => 'パスワードは半角英数字と記号で入力してください。']);

        $this->actingAs($user)->from('/edit-password')->post(route('update-password'), [
            'current_password' => $this->validPassword(),
            'new_password' => 'Ab1!xyz',
            'new_password_confirmation' => 'Ab1!xyz',
        ])->assertSessionHasErrors(['new_password' => 'パスワードは8文字以上64文字以内で入力してください。']);

        $this->actingAs($user)->from('/edit-password')->post(route('update-password'), [
            'current_password' => $this->validPassword(),
            'new_password' => $this->validPassword(),
            'new_password_confirmation' => '',
        ])->assertSessionHasErrors(['new_password_confirmation' => 'パスワード（確認用）を入力してください。']);

        $this->actingAs($user)->from('/edit-password')->post(route('update-password'), [
            'current_password' => $this->validPassword(),
            'new_password' => $this->validPassword(),
            'new_password_confirmation' => 'Other1!x',
        ])->assertSessionHasErrors(['new_password_confirmation' => 'パスワードが一致していません。']);

        $this->actingAs($user)->from('/edit-password')->post(route('update-password'), [
            'current_password' => $this->validPassword(),
            'new_password' => 'abcdefghij',
            'new_password_confirmation' => 'abcdefghij',
        ])->assertSessionHasErrors('new_password');
    }

    public function test_password_guest_and_csrf_and_confirmation_name(): void
    {
        $user = $this->makeUser();
        $hash = $user->password;

        $this->get('/edit-password')->assertRedirect(route('login'));
        $this->post(route('update-password'), [
            'current_password' => $this->validPassword(),
            'new_password' => 'NewPass1!',
            'new_password_confirmation' => 'NewPass1!',
        ])->assertRedirect(route('login'));
        $this->assertSame($hash, $user->fresh()->password);

        $html = $this->actingAs($user)->get('/edit-password')->assertOk()->getContent();
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="new_password_confirmation"', $html);
    }

    // --- dsc_06edit-icon.md ---

    public function test_icon_upload_update_cancel_reset(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();

        $this->actingAs($user)->get('/edit-icon')->assertOk();

        $this->actingAs($user)
            ->post(route('edit-icon.update'))
            ->assertRedirect('edit-icon')
            ->assertSessionHasErrors();

        $file = UploadedFile::fake()->image('icon.png', 100, 100);
        $this->actingAs($user)
            ->post(route('edit-icon.upload'), ['icon' => $file])
            ->assertRedirect('edit-icon');

        $temp = session('temp_icon');
        $this->assertNotEmpty($temp);
        Storage::disk('local')->assertExists($temp);

        $this->actingAs($user)->get('/edit-icon')->assertOk();

        $this->actingAs($user)
            ->post(route('edit-icon.update'))
            ->assertRedirect('account');

        $user->refresh();
        $this->assertNotNull($user->icon);
        Storage::disk('local')->assertExists($user->icon);
        $this->assertNull(session('temp_icon'));

        $this->actingAs($user)->get('/account')->assertOk();
        $this->actingAs($user)->get('/edit-icon')->assertOk();

        $production = $user->icon;

        $file2 = UploadedFile::fake()->image('icon2.png', 80, 80);
        $this->actingAs($user)
            ->post(route('edit-icon.upload'), ['icon' => $file2])
            ->assertRedirect('edit-icon');
        $temp2 = session('temp_icon');
        Storage::disk('local')->assertExists($temp2);

        $this->actingAs($user)
            ->post(route('edit-icon.cancel'))
            ->assertRedirect('account');
        Storage::disk('local')->assertMissing($temp2);
        $this->assertSame($production, $user->fresh()->icon);
        $this->assertNull(session('temp_icon'));

        $this->actingAs($user)
            ->post(route('edit-icon.reset'))
            ->assertRedirect('edit-icon');
        $this->assertNull($user->fresh()->icon);
    }

    public function test_icon_upload_validation(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();

        $this->actingAs($user)->from('/edit-icon')->post(route('edit-icon.upload'), [])
            ->assertSessionHasErrors(['icon' => '画像がアップロードされていません']);

        $this->actingAs($user)->from('/edit-icon')->post(route('edit-icon.upload'), [
            'icon' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['icon' => 'PNG または JPEG 形式の画像をアップロードしてください']);

        $this->actingAs($user)->from('/edit-icon')->post(route('edit-icon.upload'), [
            'icon' => UploadedFile::fake()->image('big.png', 401, 401),
        ])->assertSessionHasErrors(['icon' => '画像サイズは400px × 400px以下にしてください']);
    }

    public function test_icon_guest_cannot_access(): void
    {
        $this->get('/edit-icon')->assertRedirect(route('login'));
        $this->post(route('edit-icon.upload'))->assertRedirect(route('login'));
        $this->post(route('edit-icon.update'))->assertRedirect(route('login'));
        $this->post(route('edit-icon.cancel'))->assertRedirect(route('login'));
        $this->post(route('edit-icon.reset'))->assertRedirect(route('login'));
    }
}
