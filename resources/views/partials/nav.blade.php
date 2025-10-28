<nav class="appbar">
  <div class="appbar__inner">
    <div class="appbar__left">
      <span class="appbar__title">Carga Horaria</span>
    </div>
    <div class="appbar__right">
      @auth
        <a class="btn btn--text" href="{{ route('admin.dashboard') }}">Dashboard</a>
        <form method="POST" action="{{ route('logout') }}" class="inline">
          @csrf
          <button type="submit" class="btn btn--text">Salir</button>
        </form>
      @endauth
      @guest
        <a class="btn btn--text" href="{{ route('login') }}">Ingresar</a>
      @endguest
    </div>
  </div>
</nav>
