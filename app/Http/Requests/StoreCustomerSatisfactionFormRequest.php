<?php
namespace App\Http\Requests;
class StoreCustomerSatisfactionFormRequest extends CustomerSatisfactionFormRequest
{
    public function authorize(): bool { return $this->user()?->can('create', \App\Models\CustomerSatisfactionForm::class) ?? false; }
}
