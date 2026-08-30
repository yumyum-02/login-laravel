<nav class="navbar navbar-dark bg-dark navbar-expand-lg">
  <div class="container-fluid">
    <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <a class="navbar-brand" href="/dashboard">
      <i class="bi bi-house-door-fill me-2"></i>Dashboard
    </a>
    <div class="d-flex align-items-center">
      <span class="text-white me-3 d-none d-md-inline d-flex align-items-center">
        <img src="{{ asset('images/icon/default-icon.png')}}" alt="アイコン" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
        {{ Auth::user()->name }}
      </span>
    </div>
  </div>
</nav>