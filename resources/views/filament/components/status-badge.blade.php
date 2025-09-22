@if ($getRecord())
    <div style="
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        gap: 0.5rem;
        flex-wrap: wrap; /* permite que los elementos se envuelvan en pantallas pequeñas */
    ">

        <!-- Botón ir a la orden a la izquierda -->
        <a href="{{ url('admin/orders/'.$getRecord()->id.'/edit') }}" 
           target="_blank"
           style="
               display: inline-block;
               padding: 0.25rem 0.5rem;
               border-radius: 0.25rem;
               background-color: #0dcaf0;
               color: white;
               font-weight: 500;
               text-decoration: none;
               border: 2px solid #0dcaf0;
               flex: 1 1 auto; /* permite que el botón tome su ancho en pantallas pequeñas */
               min-width: 150px; /* evita que se encoja demasiado */
               text-align: center;
           ">
            Ir a la orden
        </a>

        <!-- Badges a la derecha -->
        <div style="
            display: flex; 
            gap: 0.5rem; 
            flex: 1 1 auto; 
            justify-content: flex-end;
            flex-wrap: wrap; /* apilar en pantallas pequeñas */
        ">

            <!-- Badge de facturado -->
            <span style="
                display: inline-block;
                padding: 0.25rem 0.5rem;
                border-radius: 0.25rem;
                color: {{ $getRecord()->invoiced_color }};
                border: 2px solid {{ $getRecord()->invoiced_color }};
                background-color: transparent;
                font-weight: 500;
            ">
                {{ $getRecord()->invoiced_label }}
            </span>

            <!-- Badge de estado -->
            <span style="
                display: inline-block;
                padding: 0.25rem 0.5rem;
                border-radius: 0.25rem;
                color: {{ $getRecord()->status_color }};
                border: 2px solid {{ $getRecord()->status_color }};
                background-color: transparent;
                font-weight: 500;
            ">
                {{ $getRecord()->status }}
            </span>

        </div>
    </div>
@endif
