<?php
namespace App\Http\Requests;
class UpdateCustomerSatisfactionFormRequest extends CustomerSatisfactionFormRequest
{
    public function authorize(): bool { return $this->user()?->can('update', $this->route('customerSatisfactionForm')) ?? false; }
}
