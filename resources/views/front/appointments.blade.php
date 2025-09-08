{{-- resources/views/welcome.blade.php --}}

@extends('layouts.front.simple_page')

@section('title',  " Citas - ". $generalSettings->brand_name)

@section('content')


<section class="general-section section-padding" id="">
        <div class="row">

            
           <div class="row">
                <div class="col-lg-8 mx-auto">
                  <livewire:front-booking 
                  :general-settings="$generalSettings" 
                  :contact-form="$contactForm" 
                  :countries="$countries"
              />  
                </div>
            </div>

    </div>
</section>


@endsection
