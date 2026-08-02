<div class="offcanvas offcanvas-start bg-white shadow-sm" tabindex="-1" id="sidebarMenu">
  <div class="offcanvas-header border-bottom d-md-none">
    <h5 class="offcanvas-title d-flex align-items-center">
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="/dashboard">
          <i class="bi bi-house-door-fill"></i>
          ダッシュボード
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/admin">
          <i class="bi bi-people-fill"></i>
          ユーザー一覧
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/chat">
          <i class="bi bi-chat-text-fill"></i>
          掲示板
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="/account">
          <i class="bi bi-person-fill"></i>
          アカウント情報
        </a>
      </li>
      <li class="nav-item mt-3">
        <form action="{{ route('logout') }}" method="post" class="p-2">
          @csrf
          <button type="submit" class="btn btn-outline-danger btn-sm w-100">
            <i class="bi bi-box-arrow-right me-1"></i>ログアウト
          </button>
        </form>
      </li>
    </ul>
  </div>
</div>
