<?php

namespace App\Filament\Resources;

use App\Exports\AppointmentExport;
use App\Filament\Resources\AppointmentResource\Pages;
use App\Filament\Resources\AppointmentResource\RelationManagers;
use App\Mail\AppointmentChangeStatusMail;
use App\Mail\AppointmentChangeStatusToWorkerMail;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Actions\Action as ModalAction;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;


    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Gestión de citas';
    protected static ?int $navigationSort = 50;
    // protected static ?string $navigationLabel = 'Ciudadedsadss';
    public static function getModelLabel(): string
    {
        return 'Cita';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Citas';
    }
    public static function getEloquentQuery(): Builder
    {

        $query = parent::getEloquentQuery();

        $user = Auth::user();

        // Obtener el ID del panel actual
        $currentPanelId = Filament::getCurrentPanel()?->getId();
        // Filtrar solo si estamos en el panel "personal"
        if ($user && $currentPanelId === 'personal') {
            $query->where('worker_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Select::make('worker_id')
                ->label('Empleado')
                ->relationship('worker', 'name', fn($query) => $query->canAppointment())
                ->searchable()
                ->preload()
                ->visible(fn() => Filament::getCurrentPanel()?->getId() !== 'personal')
                ->placeholder('Selecciona empleado'),

            Select::make('item_id')
                ->label('Peinado')
                ->relationship(
                    name: 'item',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn($query) => $query->active()
                )
                ->getOptionLabelFromRecordUsing(fn($record) => $record->name . ' -- ' . $record->total_price . ' €')
                ->searchable()
                ->preload()
                ->placeholder('Selecciona Servicio'),

            DatePicker::make('date')
                ->label('Fecha')
                ->required(),

            TimePicker::make('start_time')
                ->label('Hora de inicio')
                ->required()
                ->seconds(false)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $endTime = $get('end_time');

                    if ($endTime && $state > $endTime) {
                        $set('end_time', $state);
                    }
                }),

            TimePicker::make('end_time')
                ->label('Hora de fin')
                ->required()
                ->seconds(false)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $startTime = $get('start_time');

                    if ($startTime && $state < $startTime) {
                        $set('start_time', $state);
                    }
                }),

            Select::make('status')
                ->label('Estado')
                ->options([
                    'available' => 'Disponible',
                    'pending_confirmation' => 'Pendiente confirmación',
                    'confirmed' => 'Confirmado',
                    'cancelled' => 'Cancelada',
                ])
                ->required()
                ->default("available"),

            TextInput::make('requester_name')
                ->label('Nombre del solicitante')
                ->maxLength(255)
                // ->required(fn(callable $get) => filled($get('requester_email')))
                ->reactive(),

            TextInput::make('requester_email')
                ->label('Correo del solicitante')
                ->email()
                ->maxLength(255)
                //->required(fn(callable $get) => filled($get('requester_name')))
                ->reactive(),

            TextInput::make('requester_phone')
                ->label('Teléfono del solicitante')
                ->tel()
                ->maxLength(255)
                ->suffixAction(function ($get) {
                    $phone = preg_replace('/\D/', '', $get('requester_phone'));

                    return Action::make('whatsapp')
                        ->icon('heroicon-s-chat-bubble-left')
                        ->label('')
                        ->url('https://wa.me/' . $phone)
                        ->openUrlInNewTab();
                }),

            Textarea::make('comments')
                ->label('Comentarios')
                ->columnSpanFull(),

            Forms\Components\Toggle::make('active')
                ->inline(false)
                ->label("¿Activo?")
                ->required(),
        ];
    }

    public static function table(Table $table): Table
    {
        $currentPanelId = Filament::getCurrentPanel()?->getId();
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('user_id')
                //     ->numeric()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->getStateUsing(fn($record) => $record->status_name_formatted)
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        $color = $record->status_color ?? '#6c757d';

                        return "<span style='
        display: inline-block;
        padding: 0.05rem 0.25rem;
        font-size: 0.55rem;
        font-weight: 600;
        color: white;
        background-color: {$color};
        border-radius: 9999px;
        text-transform: uppercase;
    '>{$state}</span>";
                    }),


                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Fecha')      // Etiqueta de la columna
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return Carbon::parse($state)->format('d-m-Y'); // Formato día-mes-año
                    }),

                Tables\Columns\TextColumn::make('start_time')
                    ->date()
                    ->label('Hora inicio')  // Etiqueta
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return Carbon::parse($state)->format('H:i'); // Formato hora:minutos (24h)
                    }),

                Tables\Columns\TextColumn::make('end_time')
                    ->date()
                    ->label('Hora final')   // Etiqueta
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return Carbon::parse($state)->format('H:i'); // Formato hora:minutos (24h)
                    }),

                Tables\Columns\TextColumn::make('worker.name')
                    ->numeric()
                    ->label('Empleado')   // Etiqueta de la columna
                    ->searchable()        // Se puede buscar en esta columna
                    ->visible(fn() => Filament::getCurrentPanel()?->getId() !== 'personal')
                    ->sortable(),         // Se puede ordenar por esta columna
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Servicio')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $state . ' -- ' . ($record->item?->total_price ?? 0) . ' €'
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('requester_name')->label("Nombre solicitante")
                    ->searchable(),   // Buscable
                Tables\Columns\TextColumn::make('requester_email')->label("Correo solicitante")
                    ->searchable(),   // Buscable

                Tables\Columns\TextColumn::make('requester_phone')->label("telefono solicitante")
                    ->searchable() // Buscable
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Se puede ocultar/mostrar por defecto oculto
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->label("¿Activo?"),
                Tables\Columns\IconColumn::make('notification_sended')
                    ->boolean()
                    ->label("¿Aviso enviado?"),
                Tables\Columns\TextColumn::make('template.name')
                    ->numeric()
                    ->label('Plantilla')   // Etiqueta de la columna
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)       // Se puede buscar en esta columna
                    ->sortable(),

                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true), // Comentado (columna actualizada)

            ])
            ->filters([
                Filter::make('filter_appointment')
                    ->form([
                        Select::make('worker_id')
                            ->label('Empleado')
                            ->relationship('worker', 'name', fn($query) => $query->canAppointment()) // <--- Aplica el scope
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecciona empleado')->visible(fn() => Filament::getCurrentPanel()?->getId() !== 'personal'),


                        DatePicker::make('date_from')
                            ->label('Fecha inicio')
                            ->default(Carbon::now()->startOfWeek()),

                        DatePicker::make('date_until')
                            ->label('Fecha fin'),
                        // ->default(Carbon::now()->endOfWeek()),

                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'available' => 'Disponible',
                                'confirmed' => 'Confirmado',
                                'pending_confirmation' => 'Pendiente confirmación',
                                //'accepted' => 'Aceptada',
                                'cancelled' => 'Cancelada',
                            ])
                            ->nullable()
                            ->placeholder('Sin estado'),
                        Select::make('active')
                            ->label('Activo')
                            ->options([
                                1 => "Activo",
                                0 => "No activo",
                            ])
                            ->nullable()
                            ->placeholder('Todos'),
                        Select::make('notification_sended')
                            ->label('Aviso enviado')
                            ->options([
                                1 => "Si",
                                0 => "No",
                            ])
                            ->nullable()
                            ->placeholder('Todos'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $filter = [];

                        if (!empty($data['worker_id'])) {
                            $filter['worker_id'] = "Trabajador: " . $data['worker_id']; // Si tienes nombres, mejor mostrar nombre
                        }

                        if (!empty($data['date_from'])) {
                            $filter['date_from'] = "Desde " . \Carbon\Carbon::parse($data['date_from'])->format('d-m-Y');
                        }

                        if (!empty($data['date_until'])) {
                            $filter['date_until'] = "Hasta " . \Carbon\Carbon::parse($data['date_until'])->format('d-m-Y');
                        }

                        if (isset($data['status']) && $data['status'] !== null) {
                            // $filter['status'] = "Estado: " . $data['status'];
                            if (isset($data['status']) && $data['status'] !== null) {
                                $statusTranslations = [
                                    'available' => 'Disponible',
                                    'pending_confirmation' => 'Pendiente confirmación',
                                    'confirmed' => 'Confirmado',
                                    //'accepted' => 'Aceptada',
                                    'cancelled' => 'Cancelada',
                                    'expired' => 'Expirada', // si lo usas
                                ];

                                $statusLabel = $statusTranslations[$data['status']] ?? $data['status'];

                                $filter['status'] = "Estado: " . $statusLabel;
                            }
                        }

                        if (isset($data['active']) && $data['active'] !== null) {
                            $filter['active'] = $data['active'] ? 'Activo' : 'Inactivo';
                        }
                        if (isset($data['notification_sended']) && $data['notification_sended'] !== null) {
                            $filter['notification_sended'] = $data['notification_sended'] ? 'Aviso enviado' : 'Aviso no enviado';
                        }

                        return $filter;
                    })

                    ->query(function ($query, array $data) {
                        if (!empty($data['worker_id'])) {
                            $query->where('worker_id', $data['worker_id']);
                        }

                        if (!empty($data['date_from'])) {
                            $query->whereDate('date', '>=', $data['date_from']);
                        }

                        if (!empty($data['date_until'])) {
                            $query->whereDate('date', '<=', $data['date_until']);
                        }

                        if (isset($data['status']) && $data['status'] !== null) {
                            $query->where('status', $data['status']);
                        }
                        if (isset($data['active']) && $data['active'] !== null) {
                            $query->where('active', $data['active']);
                        }
                        if (isset($data['notification_sended']) && $data['notification_sended'] !== null) {
                            $query->where('notification_sended', $data['notification_sended']);
                        }

                        return $query;
                    }),
            ])

            ->actions([

                TableAction::make('confirmNotification')
                    ->label('')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->tooltip('Confirmar la solicitud y/o enviar notificación')
                    ->visible(fn($record) => in_array($record->status, ['cancelled', 'pending_confirmation']))
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar solicitud')
                    ->modalSubmitActionLabel('Confirmar')   // botón principal
                    ->modalCancelActionLabel('Regresar')    // cerrar modal
                    ->form([
                        \Filament\Forms\Components\Checkbox::make('send_notification')
                            ->label('Enviar notificación por correo')
                            ->default(false),
                    ])
                    ->action(function ($record, array $data) {
                        $record->status = 'confirmed';
                        $record->save();

                        if (!empty($data['send_notification']) && $record->requester_email) {
                            Mail::to($record->requester_email)
                                ->send(new AppointmentChangeStatusMail($record));

                            if ($record->worker && $record->worker->email) {
                                Mail::to($record->worker->email)
                                    ->send(new AppointmentChangeStatusToWorkerMail($record));
                            }

                            $record->notification_sended = true;
                            $record->save();

                            Notification::make()
                                ->title('Solicitud confirmada y notificación enviada')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Solicitud confirmada')
                                ->success()
                                ->send();
                        }
                    }),
                TableAction::make('cancelNotification')
                    ->label('')
                    ->icon('heroicon-m-x-circle') // aspa roja
                    ->color('danger') // rojo
                    ->tooltip('Cancelar la solicitud y/o enviar notificación')
                    ->visible(function ($record) {
                        return in_array($record->status, ['pending_confirmation', 'confirmed'])
                            && !\App\Models\Order::where('appointment_id', $record->id)->exists();
                    })

                    ->requiresConfirmation()
                    ->modalHeading('Cancelar solicitud')
                    ->modalSubmitActionLabel('Cancelar')   // botón principal
                    ->modalCancelActionLabel('Regresar')   // cerrar modal
                    ->form([
                        \Filament\Forms\Components\Checkbox::make('send_notification')
                            ->label('Enviar notificación por correo')
                            ->default(false),
                    ])
                    ->action(function ($record, array $data) {
                        $record->status = 'cancelled';
                        $record->save();

                        if (!empty($data['send_notification']) && $record->requester_email) {
                            Mail::to($record->requester_email)
                                ->send(new AppointmentChangeStatusMail($record));

                            if ($record->worker && $record->worker->email) {
                                Mail::to($record->worker->email)
                                    ->send(new AppointmentChangeStatusToWorkerMail($record));
                            }
                            $record->notification_sended = true;
                            $record->save();

                            Notification::make()
                                ->title('Solicitud cancelada y notificación enviada')
                                ->danger() // rojo
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Solicitud cancelada')
                                ->danger() // rojo
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('sendNotification')
                    ->label('')
                    ->icon('heroicon-o-envelope')
                    ->color('success') // azul
                    ->tooltip('Enviar notificación por correo electrónico')
                    ->visible(fn($record) => in_array($record->status, ['confirmed', 'cancelled']) &&  $record->requester_email)

                    ->action(function ($record) {
                        Mail::to($record->requester_email)
                            ->send(new AppointmentChangeStatusMail($record));

                        if ($record->worker && $record->worker->email) {
                            Mail::to($record->worker->email)
                                ->send(new AppointmentChangeStatusToWorkerMail($record));
                        }
                        $record->notification_sended = true;
                        $record->save();

                        Notification::make()
                            ->title('Notificación enviada por correo')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('whatsapp')
                    ->icon('heroicon-s-chat-bubble-left')
                    ->label('')
                    ->tooltip('Enviar WhatsApp')
                    ->url(fn($record) => 'https://wa.me/' . preg_replace('/\D/', '', $record->requester_phone))
                    ->openUrlInNewTab()
                    ->visible(
                        fn($record) =>
                        !empty($record->requester_phone) && in_array($record->status, ['cancelled', 'confirmed'])
                    ),
                TableAction::make('convertToInvoice')
                    ->label('')
                    ->icon('heroicon-m-document-text') // ícono de documento
                    ->color('warning') // verde
                    ->tooltip('Convertir esta cita en una factura')
                    ->visible(function ($record) {
                        $currentPanelId = Filament::getCurrentPanel()?->getId();

                        return $record->status === 'confirmed'
                            && $currentPanelId === 'admin'
                            && !\App\Models\Order::where('appointment_id', $record->id)->exists();
                    })

                    ->requiresConfirmation()
                    ->modalHeading('¿Convertir esta cita en factura?') // mensaje de confirmación
                    ->modalSubmitActionLabel('Sí, convertir')
                    ->modalCancelActionLabel('No')
                    ->action(function ($record) {
                        // Aquí lógica para convertir cita a factura
                        $order = Order::create([
                            'type' => 'sale',
                            'status' => 'pending',
                            'customer_id' => 1, // si el worker es el cliente
                            'date' => $record->date,
                            'assigned_user_id' => $record->worker_id,
                            'appointment_id' => $record->id,
                        ]);
                        if ($record->item) {
                            OrderDetail::create([
                                'order_id' => $order->id,
                                'item_id' => $record->item_id,
                                'price' => $record->item->price ?? 0,
                                'original_price' => $record->item->price ?? 0,
                                'taxes' => 0,
                                'quantity' => 1,
                            ]);
                        }

                        Notification::make()
                            ->title('La cita se ha convertido en factura')
                            ->success()
                            ->send();
                    }),

                TableAction::make('viewInvoice')
                    ->label('')
                    ->icon('heroicon-m-eye') // ojo para "ver"
                    ->color('tertiary') // verde
                    ->tooltip('Ver factura asociada')
                    ->visible(function ($record) {
                        $currentPanelId = Filament::getCurrentPanel()?->getId();

                        return $currentPanelId === 'admin'
                            && \App\Models\Order::where('appointment_id', $record->id)->exists();
                    })
                    ->url(function ($record) {
                        $order = Order::where('appointment_id', $record->id)->first();
                        return $order
                            ? route('filament.admin.resources.sales.edit', $order)
                            : null;
                    })
                    ->openUrlInNewTab(), // opcional: abre en nueva pestaña



                /*Tables\Actions\EditAction::make()->label('')->tooltip('Editar'),
                Tables\Actions\DeleteAction::make()->label('')->tooltip('Eliminar')->visible(fn($record) => $record->status == "available"),*/
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->visible(fn($record) => ! \App\Models\Order::where('appointment_id', $record->id)->exists()),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Eliminar')
                    ->visible(
                        fn($record) =>
                        $record->status === "available"
                            && ! \App\Models\Order::where('appointment_id', $record->id)->exists()
                    ),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Aquí puedes activar DeleteBulkAction si lo necesitas
                    // Tables\Actions\DeleteBulkAction::make(),

                    BulkAction::make('change_active_status')
                        ->label('Cambiar estado de actividad')
                        //->icon('heroicon-o-toggle-right')
                        ->color('warning')
                        ->form([
                            Select::make('active')
                                ->label('¿Activar o desactivar?')
                                ->options([
                                    '1' => 'Activar',
                                    '0' => 'Desactivar',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->update([
                                    'active' => $data['active'],
                                ]);
                            }

                            Notification::make()
                                ->title('Estado actualizado correctamente')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('delete_available')
                        ->label('Eliminar disponibles')
                        ->color('danger')
                        ->requiresConfirmation() // <-- mensaje de confirmación
                        ->modalHeading('Confirmar eliminación')
                        ->modalSubheading('¿Seguro que deseas eliminar los registros seleccionados que estén disponibles? Esta acción no se puede deshacer.')
                        ->modalButton('Sí, eliminar')
                        ->action(function ($records) {
                            $deletedCount = 0;

                            foreach ($records as $record) {
                                if ($record->status === 'available') {
                                    $record->delete();
                                    $deletedCount++;
                                }
                            }

                            Notification::make()
                                ->title("Se eliminaron {$deletedCount} registros disponibles")
                                ->success()
                                ->send();
                        }),
                ]),
                BulkAction::make('export')->label('Exportar ' . self::getPluralModelLabel())->icon('heroicon-m-arrow-down-tray')
                    ->action(function ($records) {

                        $modelLabel = self::getPluralModelLabel();
                        // Puedes agregar la fecha o cualquier otro dato para personalizar el nombre
                        $fileName = $modelLabel . '-' . now()->format('d-m-Y') . '.xlsx'; // Ejemplo: "Marcas-2025-03-14.xlsx"

                        // Preparamos la consulta para exportar
                        $query = \App\Models\Appointment::whereIn('id', $records->pluck('id'));

                        // Llamamos al método Excel::download() y pasamos el nombre dinámico del archivo
                        return Excel::download(new AppointmentExport($query), $fileName);
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
