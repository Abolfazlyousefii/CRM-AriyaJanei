<?php

namespace Tests\Feature;

use App\Models\CustomerSatisfactionForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerSatisfactionFormTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User { $u = User::factory()->create(); Role::findOrCreate($role, 'web'); $u->assignRole($role); return $u; }
    private function buyer(array $overrides=[]): array { return array_merge(['customer_full_name'=>'مشتری خریدار','customer_phone'=>'۰۹۱۲-۱۲۳ ۴۵۶۷','purchase_status'=>'purchased','sales_response_score'=>5,'support_positive_features'=>['communication'],'warranty_explained'=>'yes','warranty_meets_needs'=>'yes','shipping_time_score'=>4,'packaging_quality_score'=>3,'product_value_satisfied'=>'yes','would_recommend'=>'yes','would_choose_again'=>'yes'], $overrides); }
    private function nonBuyer(array $overrides=[]): array { return array_merge(['customer_full_name'=>'مشتری بدون خرید','purchase_status'=>'not_purchased','no_purchase_reason'=>'price'], $overrides); }
    private function post(User $u,array $customer) { return $this->actingAs($u)->post(route('customer-satisfaction-forms.store'),['customers'=>[$customer]]); }

    public function test_customer_review_can_store_purchased_form(): void { $this->post($this->user('customer_review'),$this->buyer())->assertSessionHasNoErrors(); $this->assertDatabaseHas('customer_satisfaction_forms',['purchase_status'=>'purchased']); }
    public function test_customer_review_can_store_not_purchased_form(): void { $this->post($this->user('customer_review'),$this->nonBuyer())->assertSessionHasNoErrors(); $this->assertDatabaseHas('customer_satisfaction_forms',['no_purchase_reason'=>'price']); }
    public function test_user_without_role_cannot_store(): void { $this->post(User::factory()->create(),$this->buyer())->assertForbidden(); }
    public function test_reason_is_required_for_not_purchased(): void { $this->post($this->user('customer_review'),$this->nonBuyer(['no_purchase_reason'=>null]))->assertSessionHasErrors('customers.0.no_purchase_reason'); }
    public function test_reason_is_cleared_for_purchased(): void { $this->post($this->user('customer_review'),$this->buyer(['no_purchase_reason'=>'price'])); $this->assertDatabaseHas('customer_satisfaction_forms',['purchase_status'=>'purchased','no_purchase_reason'=>null]); }
    public function test_buyer_questions_are_required(): void { $this->post($this->user('customer_review'),['customer_full_name'=>'الف','purchase_status'=>'purchased'])->assertSessionHasErrors('customers.0.sales_response_score'); }
    public function test_score_below_one_is_rejected(): void { $this->post($this->user('customer_review'),$this->buyer(['sales_response_score'=>0]))->assertSessionHasErrors('customers.0.sales_response_score'); }
    public function test_score_above_five_is_rejected(): void { $this->post($this->user('customer_review'),$this->buyer(['shipping_time_score'=>6]))->assertSessionHasErrors('customers.0.shipping_time_score'); }
    public function test_invalid_yes_no_is_rejected(): void { $this->post($this->user('customer_review'),$this->buyer(['would_recommend'=>'maybe']))->assertSessionHasErrors('customers.0.would_recommend'); }
    public function test_invalid_support_feature_is_rejected(): void { $this->post($this->user('customer_review'),$this->buyer(['support_positive_features'=>['invalid']]))->assertSessionHasErrors('customers.0.support_positive_features.0'); }
    public function test_multiple_customers_are_atomic_and_saved(): void { $u=$this->user('customer_review'); $this->actingAs($u)->post(route('customer-satisfaction-forms.store'),['customers'=>[$this->buyer(),$this->nonBuyer()]])->assertSessionHasNoErrors(); $this->assertDatabaseCount('customer_satisfaction_forms',2); }
    public function test_invalid_batch_saves_nothing(): void { $u=$this->user('customer_review'); $this->actingAs($u)->post(route('customer-satisfaction-forms.store'),['customers'=>[$this->buyer(),$this->nonBuyer(['no_purchase_reason'=>null])]]); $this->assertDatabaseCount('customer_satisfaction_forms',0); }
    public function test_review_user_sees_own_and_assigned_only(): void { $u=$this->user('customer_review'); $own=CustomerSatisfactionForm::factory()->create(['created_by_user_id'=>$u->id]); $other=CustomerSatisfactionForm::factory()->create(); $this->actingAs($u)->get(route('customer-satisfaction-forms.index'))->assertSee((string)$own->id)->assertDontSee((string)$other->id); }
    public function test_admin_sees_all_forms(): void { $u=$this->user('Admin'); $form=CustomerSatisfactionForm::factory()->create(); $this->actingAs($u)->get(route('customer-satisfaction-forms.index'))->assertSee((string)$form->id); }
    public function test_creator_can_update_new_form(): void { $u=$this->user('customer_review'); $form=CustomerSatisfactionForm::factory()->create(['created_by_user_id'=>$u->id,'purchase_status'=>'not_purchased']); $this->actingAs($u)->put(route('customer-satisfaction-forms.update',$form),['customers'=>[$this->nonBuyer(['customer_full_name'=>'نام جدید'])]])->assertSessionHasNoErrors(); }
    public function test_unauthorized_user_cannot_update(): void { $form=CustomerSatisfactionForm::factory()->create(['purchase_status'=>'not_purchased']); $this->actingAs($this->user('customer_review'))->put(route('customer-satisfaction-forms.update',$form),['customers'=>[$this->nonBuyer()]])->assertForbidden(); }
    public function test_assigned_user_can_submit_result(): void { $u=$this->user('customer_review'); $form=CustomerSatisfactionForm::factory()->create(['assigned_to_user_id'=>$u->id]); $this->actingAs($u)->patch(route('customer-satisfaction-forms.submit-result',$form),['result'=>'بررسی شد'])->assertRedirect(); }
    public function test_other_user_cannot_submit_result(): void { $form=CustomerSatisfactionForm::factory()->create(); $this->actingAs($this->user('customer_review'))->patch(route('customer-satisfaction-forms.submit-result',$form),['result'=>'خیر'])->assertForbidden(); }
    public function test_legacy_form_can_be_viewed(): void { $u=$this->user('customer_review'); $form=CustomerSatisfactionForm::factory()->create(['created_by_user_id'=>$u->id,'purchase_status'=>null]); $this->actingAs($u)->get(route('customer-satisfaction-forms.show',$form))->assertOk()->assertSee('فرم قدیمی'); }
    public function test_form_with_result_cannot_be_deleted(): void { $u=$this->user('customer_review'); $form=CustomerSatisfactionForm::factory()->create(['created_by_user_id'=>$u->id,'result'=>'ثبت شده']); $this->actingAs($u)->delete(route('customer-satisfaction-forms.destroy',$form))->assertForbidden(); }
    public function test_index_filters_by_purchase_status(): void { $u=$this->user('Admin'); $wanted=CustomerSatisfactionForm::factory()->create(['purchase_status'=>'not_purchased']); $other=CustomerSatisfactionForm::factory()->create(['purchase_status'=>'purchased']); $this->actingAs($u)->get(route('customer-satisfaction-forms.index',['purchase_status'=>'not_purchased']))->assertSee((string)$wanted->id)->assertDontSee((string)$other->id); }
}
