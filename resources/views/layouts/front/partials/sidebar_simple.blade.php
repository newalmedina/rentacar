

<button class="navbar-toggler d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
</button>

<nav id="sidebarMenu" class="col-md-4 col-lg-3 d-md-block sidebar collapse p-0">

    <div class="position-sticky sidebar-sticky d-flex flex-column justify-content-center align-items-center">
        <a class="navbar-brand" href="index.html">
            <img src =" {{ asset('assets/front/images/templatemo-barber-logo.png') }}" style="width: 100%" class="logo-image img-fluid" align="">
        </a>

       <ul class="nav flex-column">
    @if($jumbotron->active)
    <li class="nav-item">
        <a target="_blank" class="nav-link click-scroll active" href="{{ route('welcome') }}#section_1">Home</a>
    </li>
    @endif

    @if($aboutUs->active)
    <li class="nav-item">
        <a target="_blank" class="nav-link click-scroll inactive" href="{{ route('welcome') }}#section_2">Sobre nosotros</a>
    </li>
    @endif

    @if($service->active)
    <li class="nav-item">
        <a target="_blank" class="nav-link click-scroll inactive" href="{{ route('welcome') }}#section_3">Servicios</a>
    </li>
    @endif

    @if($priceList->active)
    <li class="nav-item">
        <a target="_blank" class="nav-link click-scroll inactive" href="{{ route('welcome') }}#section_4">Algunos precios</a>
    </li>
    @endif

    @if($contactForm->active)
    <li class="nav-item">
        <a target="_blank" class="nav-link click-scroll inactive" href="{{ route('welcome') }}#section_5">Contactenos</a>
    </li>
    @endif
</ul>

        </ul>
    </div>
</nav>