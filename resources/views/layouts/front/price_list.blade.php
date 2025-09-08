@if($priceList->active)
<section class="price-list-section section-padding" id="section_4">
    <div class="container">
        <div class="row">

            <div class="col-lg-8 col-12">
                <div class="price-list-thumb-wrap">
                    <div class="mb-4">
                        <h2 class="mb-2">{{ $priceList->title }}</h2>

                        <strong>{{ $priceList->subtitle }}</strong>
                    </div>

                    @foreach ($priceList->activeImages as $item)
                        <div class="price-list-thumb">
                            <h6 class="d-flex">
                                {{ $item->title }}
                                <span class="price-list-thumb-divider"></span>

                                <strong> {{ $item->subtitle }}€</strong>
                            </h6>
                        </div>                      
                   @endforeach

                   
                </div>
            </div>

            @if (!empty($priceList->image_path))
                <div class="col-lg-4 col-12 custom-block-bg-overlay-wrap mt-5 mb-5 mb-lg-0 mt-lg-0 pt-3 pt-lg-0">
                    <img src ="{{ $priceList->image_path ? asset('storage/' . $priceList->image_path) : '' }}"class="custom-block-bg-overlay-image img-fluid" alt="">
                </div>                
            @endif

        </div>
    </div>
</section>
@endif
{{-- 
<section class="price-list-section section-padding" id="section_4">
    <div class="container">
        <div class="row">

            <div class="col-lg-8 col-12">
                <div class="price-list-thumb-wrap">
                    <div class="mb-4">
                        <h2 class="mb-2">Price List</h2>

                        <strong>Starting at $25</strong>
                    </div>

                    <div class="price-list-thumb">
                        <h6 class="d-flex">
                            Haircut
                            <span class="price-list-thumb-divider"></span>

                            <strong>$32.00</strong>
                        </h6>
                    </div>

                    <div class="price-list-thumb">
                        <h6 class="d-flex">
                            Beard Trim
                            <span class="price-list-thumb-divider"></span>

                            <strong>$26.00</strong>
                        </h6>
                    </div>

                    <div class="price-list-thumb">
                        <h6 class="d-flex">
                            Razor Cut
                            <span class="price-list-thumb-divider"></span>

                            <strong>$36.00</strong>
                        </h6>
                    </div>

                    <div class="price-list-thumb">
                        <h6 class="d-flex">
                            Shaves
                            <span class="price-list-thumb-divider"></span>

                            <strong>$30.00</strong>
                        </h6>
                    </div>

                    <div class="price-list-thumb">
                        <h6 class="d-flex">
                            Styling / Color
                            <span class="price-list-thumb-divider"></span>

                            <strong>$25.00</strong>
                        </h6>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-12 custom-block-bg-overlay-wrap mt-5 mb-5 mb-lg-0 mt-lg-0 pt-3 pt-lg-0">
                <img src =" {{ asset('assets/front/images/vintage-chair-barbershop.png') }}"class="custom-block-bg-overlay-image img-fluid" alt="">
            </div>

        </div>
    </div>
</section> --}}