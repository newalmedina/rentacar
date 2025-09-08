@if($jumbotron->active)
<section class="hero-section d-flex justify-content-center align-items-center" id="section_1"
 style="background-image: url('{{ $jumbotron->image_path ? asset('storage/' . $jumbotron->image_path) : asset('assets/front/images/client-doing-hair-cut-barber-shop-salon.png') }}')"
>

    <div class="container">
        <div class="row">

            <div class="col-lg-8 col-12">
                <h1 class="text-white mb-lg-3 mb-4"><strong> {{ Str::before($jumbotron->title, ' ') }} <em>{{ Str::after($jumbotron->title, ' ') }}</em></strong></h1>
                <p class="text-white">{{ $jumbotron->subtitle }}</p>
                <br>
                <a class="btn custom-btn custom-border-btn custom-btn-bg-white smoothscroll me-2 mb-2" href="#section_2">Sobre Nosotros</a>

                <a class="btn custom-btn smoothscroll mb-2" href="#section_3">Nuestros Servicios</a>
            </div>
        </div>
    </div>

    @if($generalSettings->allow_appointment)
        <div class="custom-block d-lg-flex flex-column justify-content-center align-items-center">
            <img src ="{{ asset('assets/front/images/templatemo-barber-logo.png') }}" class="custom-block-image img-fluid" alt="">

            
            <h4><strong class="text-white">{{ $jumbotron->secondary_text }}</strong></h4>
<a href="{{ route('booking') }}" class="smoothscroll btn custom-btn custom-btn-italic mt-3">
    Reserva tu cita
</a>

        </div>
    @endif
</section>
@endif


{{-- <section class="hero-section d-flex justify-content-center align-items-center" id="section_1">

    <div class="container">
        <div class="row">

            <div class="col-lg-8 col-12">
                <h1 class="text-white mb-lg-3 mb-4"><strong>By Estrella <em>Salón de Trenzas</em></strong></h1>
                <p class="text-white">Luce las trenzas más profesionales y modernas hechas especialmente para ti</p>
                <br>
                <a class="btn custom-btn custom-border-btn custom-btn-bg-white smoothscroll me-2 mb-2" href="#section_2">Sobre Nosotros</a>

                <a class="btn custom-btn smoothscroll mb-2" href="#section_3">Nuestros Servicios</a>
            </div>
        </div>
    </div>

    <div class="custom-block d-lg-flex flex-column justify-content-center align-items-center">
        <img src ="{{ asset('assets/front/images/templatemo-barber-logo.png') }}" class="custom-block-image img-fluid" alt="">


        
        <h4><strong class="text-white">¡Date prisa! Reserva tu trenza perfecta.</strong></h4>

        <a href="#booking-section" class="smoothscroll btn custom-btn custom-btn-italic mt-3">Reserva tu cita</a>
    </div>
</section> --}}
