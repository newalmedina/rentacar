<?php

namespace App\Livewire\Sales;

use App\Mail\ReceiptMail;
use App\Models\{Customer, Item, Order, OrderDetail, Setting, User};
use App\Services\ReceiptService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class Form extends Component
{
    use WithPagination;

    public ?Order $order = null;
    public string $searchProduct = '';
    public string $searchType = '';
    public string $actionType = 'new';
    public int|string $perPage = 10;
    public $editPrices = false;

    public  $userList = [];
    public array $inputValues = [];
    public array $selectedProducts = [];
    public array $getGeneralTotals = ["subtotal" => 0, "total" => 0, "taxes_amount" => 0];
    public array $manualProduct = [];
    public array $detail_id_delete = [];
    public string $recipientType = 'same';
    public string $recipientEmail = '';
    public array $form = [
        'date' => '',
        'iva' => '',
        'customer_id' => '',
        'assigned_user_id' => '',
        'observations' => '',
        'billing_name' => "",
        'billing_nif' => "",
        'billing_email' => "",
        'billing_phone' => "",
        'billing_address' => "",
        'payment_method' => "",
    ];

    public function mount($order = null): void
    {

        $this->userList = User::all();

        if (!$order) {
            $order = new Order();
            $order->assigned_user_id = auth()->user()->id;
            $this->form["assigned_user_id"] = auth()->user()->id;
        }

        $this->resetManualProduct();
        $this->order = $order;
        // Al cargar, establecer el email del cliente por defecto
        if ($this->order->customer?->email) {
            $this->recipientEmail = $this->order->customer->email;
        }
        if (!empty($this->order->id)) {
            $this->actionType = "update";
        }


        foreach ($this->order->orderDetails   as $detail) {

            $index = empty($detail->item_id) ? Str::uuid()->toString() : $detail->item_id;
            $aleatory = empty($detail->item_id) ?  $index : null;


            $this->selectedProducts[$index] = [
                "aleatory_id" =>  $aleatory,
                "detail_id" => $detail->id,
                "image_url" => !empty($detail->item_id) ? $detail->item->image_url : null,
                "item_id" => $detail->item_id,
                "item_name" => !empty($detail->item_id) ? $detail->item->name : $detail->product_name,
                "item_type" => $detail->item?->type ?? 'manual_product',
                "price_unit" => $detail->price,
                "price" => $detail->price * $detail->quantity,

                "taxes" => $detail->taxes,
                "taxes_amount" =>    $detail->taxes_amount * $detail->quantity,
                "quantity" => $detail->quantity,

                "price_with_taxes" =>  round($detail->total_price * $detail->quantity, 2),
                "total" => round($detail->total_price * $detail->quantity, 2),
            ];
        }


        if ($order) {

            $this->form = $order->only(array_keys($this->form));
        }

        $this->form['date'] = $this->form['date'] ?: Carbon::now()->format("Y-m-d");

        foreach ($this->consultaItems->get() as $item) {
            $this->inputValues[$item->id] = $item->type === 'service' ? 1 : ($item->amount == 0 ? 0 : 1);
        }
    }

    public function changeRecipientType()
    {
        if ($this->recipientType === 'same') {
            $this->recipientEmail = $this->order->customer->email ?? '';
        } else {
            $this->recipientEmail = '';
        }
    }

    public function sendInvoiceEmail()
    {
        $this->validate([
            'recipientEmail' => 'required|email',
        ], [
            'recipientEmail.required' => 'El campo correo electrónico es obligatorio.',
            'recipientEmail.email' => 'Debes ingresar un correo electrónico válido.',
        ]);

        $receiptService = new ReceiptService();
        $pdf = $receiptService->generate($this->order);

        // Enviar correo a la dirección del cliente
        Mail::to($this->recipientEmail)
            ->send(new ReceiptMail($pdf, $this->order));

        /*  Mail::to($this->order->customer->email)
            ->send(new ReceiptMail($pdf, $this->order));*/


        // Aquí se envía el correo (ejemplo genérico)
        // Mail::to($this->recipientEmail)->send(new InvoiceMail($this->order));
        $this->notify('Recibo enviado a ' . $this->recipientEmail, 'Recibo enviado',  'success');
        $this->dispatch('close-modal', id: 'send-invoiced-modal');
    }
    public function generateReceipt()
    {
        $receiptService = new ReceiptService();
        $pdf = $receiptService->generate($this->order);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $this->order->code . '.pdf');
    }

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'manualProduct.')) {
            $this->calculateManualProduct();
        }
    }


    public function buscarProducto(): void
    {
        $this->resetPage();
    }

    public function resetManualProduct(): void
    {
        $this->manualProduct = [
            "product_name" => "",
            "price" => null,
            "quantity" => null,
            "taxes" => null,
            "taxes_amount" => null,
            "price_with_taxes" => null,
            "total" => null,
        ];
    }

    /*public function calculateManualProduct(): void
    {
        if (!$this->isManualProductComplete()) {
            $this->manualProduct['taxes_amount'] = null;
            $this->manualProduct['price_with_taxes'] = null;
            $this->manualProduct['total'] = null;
            return;
        }

        $quantity = $this->manualProduct['quantity'];
        $price = $this->manualProduct['price'];
        $tax = $this->manualProduct['taxes'];

        $subtotal = $quantity * $price;
        $taxes_percent = $tax / 100;

        // Calcula el monto de impuestos, aunque sea 0
        $taxesAmount = round($subtotal * $taxes_percent, 2);

        $this->manualProduct['taxes_amount'] = $taxesAmount;
        $this->manualProduct['price_with_taxes'] = round($subtotal + $taxesAmount, 2);
        $this->manualProduct['total'] = round($subtotal + $taxesAmount, 2);
    }*/
    public function calculateManualProduct(): void
    {
        $quantity = $this->manualProduct['quantity'] ?? null;
        $price = $this->manualProduct['price'] ?? null;
        $tax = $this->manualProduct['taxes'] ?? null;

        // Validación defensiva: que los valores no estén vacíos y sean numéricos
        if (
            !$this->isManualProductComplete() ||
            !is_numeric($quantity) ||
            !is_numeric($price) ||
            !is_numeric($tax)
        ) {
            $this->manualProduct['taxes_amount'] = null;
            $this->manualProduct['price_with_taxes'] = null;
            $this->manualProduct['total'] = null;
            return;
        }

        $subtotal = (float)$quantity * (float)$price;
        $taxes_percent = (float)$tax / 100;
        $taxesAmount = round($subtotal * $taxes_percent, 2);

        $this->manualProduct['taxes_amount'] = $taxesAmount;
        $this->manualProduct['price_with_taxes'] = round($subtotal + $taxesAmount, 2);
        $this->manualProduct['total'] = round($subtotal + $taxesAmount, 2);
    }



    protected function isManualProductComplete(): bool
    {
        return !is_null($this->manualProduct['quantity'])
            && !is_null($this->manualProduct['price'])
            && !is_null($this->manualProduct['taxes']);
    }


    public function validateManualProduct(): void
    {
        $this->validate(
            [
                'manualProduct.product_name' => ['required', 'string'],
                'manualProduct.price' => ['required', 'numeric', 'min:0'],
                'manualProduct.quantity' => ['required', 'integer', 'min:1'],
                //'manualProduct.taxes' => ['required', 'numeric', 'min:0'],
            ],
            [
                'manualProduct.product_name.required' => 'El campo es obligatorio.',
                'manualProduct.product_name.string' => 'El campo debe ser un texto válido.',
                'manualProduct.price.required' => 'El campo es obligatorio.',
                'manualProduct.price.numeric' => 'El campo debe ser un número válido.',
                'manualProduct.price.min' => 'El valor no puede ser menor que 0.',
                'manualProduct.quantity.required' => 'El campo es obligatorio.',
                'manualProduct.quantity.integer' => 'El campo debe ser un número entero.',
                'manualProduct.quantity.min' => 'El valor debe ser al menos 1.',
                'manualProduct.taxes.required' => 'El campo es obligatorio.',
                'manualProduct.taxes.numeric' => 'El campo debe ser un número válido.',
                'manualProduct.taxes.min' => 'El valor no puede ser menor que 0.',
            ]
        );
    }


    public function saveManualProduct(): void
    {
        $this->validateManualProduct();

        $id = Str::uuid()->toString();
        $this->selectedProducts[$id] = [
            "aleatory_id" => $id,
            "detail_id" => null,
            "item_id" => null,
            "item_name" => $this->manualProduct["product_name"],
            "item_type" => "manual_product",
            "price_unit" => $this->manualProduct["price"],
            "price" => $this->manualProduct["price"] * $this->manualProduct["quantity"],
            "taxes" => $this->manualProduct["taxes"],
            "taxes_amount" => $this->manualProduct["taxes_amount"],
            "quantity" => $this->manualProduct["quantity"],
            "price_with_taxes" => round($this->manualProduct["price_with_taxes"], 2),
            "total" => round($this->manualProduct["total"], 2),
        ];

        $this->notify('Producto añadido correctamente', 'Producto añadido', 'success');
        $this->dispatch('close-modal', id: 'manual-product-modal');
        $this->resetManualProduct();
    }

    public function closeModalManual(): void
    {
        $this->dispatch('close-modal', id: 'manual-product-modal');
    }
    public function closeModalsendInvoiceEmail(): void
    {
        $this->dispatch('close-modal', id: 'send-invoiced-modal');
    }


    public function validateForm(): void
    {
        $this->validate(
            [
                'form.customer_id' => ['required'],
                'form.assigned_user_id' => ['required'],
                'form.date' => ['required'],
            ],
            [
                'form.customer_id.required' => 'Este campo es obligatorio.',
                'form.assigned_user_id.required' => 'Este campo es obligatorio.',
                'form.date.required' => 'Este campo es obligatorio.',
            ]
        );
    }

    public function saveForm(int $action = 0)
    {
        $this->validateForm();

        if (empty($this->selectedProducts)) {
            $this->notify('Debes seleccionar al menos 1 artículo', 'Error al guardar', 'danger');
            return;
        }

        // Guardar lógica del pedido pendiente

        $this->order->date = $this->form["date"] ? Carbon::parse($this->form["date"])->format('Y-m-d') : null;
        $this->order->customer_id = $this->form["customer_id"];
        $this->order->assigned_user_id = $this->form["assigned_user_id"];
        $this->order->observations = $this->form["observations"];
        $this->order->iva = $this->form["iva"];
        $this->order->billing_name = $this->form["billing_name"];
        $this->order->billing_nif = $this->form["billing_nif"];
        $this->order->billing_email = $this->form["billing_email"];
        $this->order->billing_phone = $this->form["billing_phone"];
        $this->order->billing_address = $this->form["billing_address"];
        $this->order->payment_method = $this->form["payment_method"];
        if ($action) {
            $this->order->status = "invoiced";
        }
        if (empty($this->order->id)) {
            $this->order->type = "sale";
        }
        $this->order->save();

        /* foreach ($this->selectedProducts as $product) {

            if (empty($product["detail_id"])) {
                $detail = new OrderDetail();
                $detail->order_id = $this->order->id;
            } else {
                $detail = OrderDetail::find($product["detail_id"]);
            }

            $detail->product_name = empty($product["item_id"]) ? $product["item_name"] : null;
            $detail->item_id = $product["item_id"];
            $detail->price = $product["price_unit"];
            $detail->taxes = $product["taxes"];
            $detail->quantity = $product["quantity"];

            $item = Item::find($product["item_id"]);
            if ($item) {
                $detail->original_price = $item->price;
            }
            $detail->save();
        }*/
        foreach ($this->selectedProducts as $product) {

            if (empty($product["detail_id"])) {
                // Buscar un detalle existente con el mismo order_id y item_id
                $detail = OrderDetail::where('order_id', $this->order->id)
                    ->where('item_id', $product["item_id"])
                    ->first();

                if (!$detail) {
                    // Si no existe, crear uno nuevo
                    $detail = new OrderDetail();
                    $detail->order_id = $this->order->id;
                }
            } else {
                // Si detail_id existe, buscarlo directamente
                $detail = OrderDetail::find($product["detail_id"]);
            }

            $detail->product_name = empty($product["item_id"]) ? $product["item_name"] : null;
            $detail->item_id = $product["item_id"];
            $detail->price = $product["price_unit"];
            $detail->taxes = $product["taxes"];
            $detail->quantity = $product["quantity"];

            $item = Item::find($product["item_id"]);
            if ($item) {
                $detail->original_price = $item->price;
            }
            $detail->save();
        }


        //eliminamo de base de datos detalles que esten quitados
        if (count($this->detail_id_delete) > 0) {
            OrderDetail::whereIn('id', $this->detail_id_delete)->delete();
            $this->detail_id_delete = [];
        }

        $this->notify(
            $action ? 'Venta facturada correctamente' : 'Venta guardada correctamente',
            $action ? 'Facturada' : 'Guardada',
            'success'
        );

        if ($this->actionType == "new") {
            return redirect("/admin/sales/{$this->order->id}/edit");
        }
    }
    public function revertStatus()
    {

        $this->order->status = "pending";

        $this->order->save();


        $this->notify(
            '',
            'Acción realizada correctamente',
            'success'
        );
    }
    public function copyCustomerInfo()
    {

        $customer = Customer::find($this->form["customer_id"]);

        // dd($this->form["customer_id"]);
        if (! $customer) {

            $this->notify(
                '',
                'No tienes ningún cliente seleccionado',
                'warning'
            );
            return false;
        }

        $this->form['billing_name'] = $customer->name;
        $this->form['billing_nif'] = $customer->identification;
        $this->form['billing_email'] = $customer->email;
        $this->form['billing_phone'] = $customer->phone;
        $this->form['billing_address'] = $customer->full_address;
        $this->notify(
            '',
            'Datos cargados correctamente',
            'success'
        );
    }

    public function selectItem($itemId): void
    {

        $item = Item::find($itemId);
        $aleatory = random_int(0, 99999);

        if (!$item) return;

        // $index = $item->id + $aleatory;
        $index = $item->id;

        $oldCantidad = 0;
        if (isset($this->selectedProducts[$index])) {
            $oldCantidad = $this->selectedProducts[$index]["quantity"];
            // $this->notify('El producto ya ha sido añadido. Modifíquelo desde productos seleccionados.', 'Producto ya añadido', 'warning');
            // return;
        }

        $quantity = $this->getQuantityItem($item->id) + $oldCantidad;

        if ($quantity <= 0) {
            $this->notify('Debes seleccionar una cantidad válida mayor a 0.', 'Cantidad inválida', 'danger');
            return;
        }

        $subtotal = $quantity * $item->price;
        $taxes_percent = $item->taxes / 100;

        $taxesAmount = round($item->price * $quantity * $taxes_percent, 2);

        $this->selectedProducts[$index] = [
            "aleatory_id" => null,
            "detail_id" => null,
            "image_url" => $item->image_url,
            "item_id" => $item->id,
            "item_name" => $item->name,
            "item_type" => $item->type,
            "price_unit" => $item->price,
            "price" => $subtotal,
            "quantity" => $quantity,

            "taxes" => $item->taxes,

            "taxes_amount" => $taxesAmount,

            "price_with_taxes" => round($subtotal + $taxesAmount, 2),


            "total" => round($subtotal + $taxesAmount, 2),
        ];

        $this->notify('Producto añadido correctamente', 'Producto añadido', 'success');
    }
    protected function recalculateProducts()
    {
        foreach ($this->selectedProducts as $key => $product) {

            // ⚠️ Convertir a float antes de operar
            $quantity = (float) ($product['quantity'] ?? 0);
            $priceUnit = (float) ($product['price_unit'] ?? 0);
            // $taxRate = (float) ($product['taxes'] ?? 0); // porcentaje (ej: 21)
            $taxRate = (float) ($this->form['iva'] ?? 0); // porcentaje (ej: 21)

            $subtotal = $priceUnit * $quantity;
            $taxesAmount = $subtotal * ($taxRate / 100);
            $priceWithTaxes = $subtotal + $taxesAmount;

            // ✅ Guardar valores redondeados (2 decimales para precios)
            $this->selectedProducts[$key] = [
                "aleatory_id"       => $product['aleatory_id'] ?? null,
                "detail_id"         => $product['detail_id'] ?? null,
                "image_url"         => $product['image_url'] ?? null,
                "item_id"           => $product['item_id'] ?? null,
                "item_name"         => $product['item_name'] ?? '',
                "item_type"         => $product['item_type'] ?? '',
                "price_unit"        => $priceUnit,
                "quantity"          => $quantity,
                "price"             => round($subtotal, 2),
                "taxes"             => $taxRate,
                "taxes_amount"      => round($taxesAmount, 2),
                "price_with_taxes"  => round($priceWithTaxes, 2),
                "total"             => round($priceWithTaxes, 2),
            ];
        }
    }

    public function deleteItem($id): void
    {

        if (isset($this->selectedProducts[$id])) {
            if (!empty($this->selectedProducts[$id]["detail_id"])) {
                $this->detail_id_delete[] = $this->selectedProducts[$id]["detail_id"];
            }
            unset($this->selectedProducts[$id]);
            $this->notify('Producto eliminado correctamente', 'Producto eliminado', 'success');
        }
    }

    private function getQuantityItem($id): float|int
    {
        return is_numeric($this->inputValues[$id] ?? 0) ? $this->inputValues[$id] + 0 : 0;
    }

    public function getConsultaItemsProperty()
    {
        return Item::active()
            ->when($this->searchProduct, fn($q) => $q->where('name', 'like', "%{$this->searchProduct}%"))
            ->when($this->searchType, fn($q) => $q->where('type', $this->searchType));
    }

    public function getGeneralTotal(): void
    {
        $this->getGeneralTotals = ['subtotal' => 0, 'total' => 0, 'taxes_amount' => 0];

        foreach ($this->selectedProducts as $key => $item) {
            $this->getGeneralTotals['total'] += $item['total'];
            $this->getGeneralTotals['subtotal'] += ($item['total'] - $item['taxes_amount']);
            $this->getGeneralTotals['taxes_amount'] += $item['taxes_amount'];
        }
    }

    private function notify(string $body, string $title, string $type): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->{$type}()
            ->duration(3000)
            ->send();
    }

    public function render()
    {
        $this->recalculateProducts();
        $this->getGeneralTotal();


        return view('livewire.sales.form', [
            'items' => $this->consultaItems->paginate($this->perPage),
            'customerList' => Customer::active()->get(),
        ]);
    }
}
