<?php
use App\Http\Controllers\Admin\CustomerAdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\MarketerController;
use App\Http\Controllers\ReferenceTypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerNotesController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserPanelController;
use App\Models\UserProduct;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\RemindersController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CustomerSatisfactionFormController;
use App\Http\Controllers\AnnouncementController;


Route::middleware(['auth'])->group(function () {

    // لیست Reminder های خود کاربر
    Route::get('/reminders', [RemindersController::class, 'index'])->name('reminders.index');

    // فرم ایجاد Reminder
    Route::get('/reminders/create', [RemindersController::class, 'create'])->name('reminders.create');

    // ذخیره Reminder جدید
    Route::post('/reminders', [RemindersController::class, 'store'])->name('reminders.store');

    // نمایش فرم ویرایش
    Route::get('/reminders/{reminder}/edit', [RemindersController::class, 'edit'])->name('reminders.edit');

    // بروزرسانی Reminder
    Route::patch('/reminders/{reminder}', [RemindersController::class, 'update'])->name('reminders.update');

    // حذف Reminder
    Route::delete('/reminders/{reminder}', [RemindersController::class, 'destroy'])->name('reminders.destroy');

    // علامت‌گذاری خوانده شده
    Route::patch('/reminders/{reminder}/seen', [RemindersController::class, 'markAsSeen'])->name('reminders.markAsSeen');

});
Route::resource('leaves', LeaveController::class);
Route::patch('leaves/{leave}/approve', [LeaveController::class,'approve'])->name('leaves.approve');
Route::patch('leaves/{leave}/reject', [LeaveController::class,'reject'])->name('leaves.reject');

Route::get('leaves', [LeaveController::class,'index'])->name('leaves');

Route::get('/', function () {
    return view('welcome');
})->name('welcome');
use App\Http\Controllers\Admin\UserBlockController;
Route::middleware(['auth','blocked'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});



Route::middleware(['role:Admin'])->prefix('admin')->name('admin.')->group(function(){
    Route::resource('tasks', TaskController::class);
});
Route::prefix('admin/tasks')->name('admin.tasks.')->group(function(){
    Route::get('/', [TaskController::class, 'index'])->name('index');
    Route::get('/create', [TaskController::class, 'create'])->name('create');
    Route::post('/', [TaskController::class, 'store'])->name('store');
    Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
    Route::put('/{task}', [TaskController::class, 'update'])->name('update');
    Route::delete('/{task}', [TaskController::class, 'destroy'])->name('destroy');
});

use App\Http\Controllers\UserManagementController;

Route::prefix('admin')->name('admin.')->middleware('role:Admin')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');

    // مدیر
    Route::get('/users/create-manager', [UserManagementController::class, 'createManager'])->name('users.createManager');
    Route::post('/users/store-manager', [UserManagementController::class, 'storeManager'])->name('users.storeManager');
    Route::get('/users/{manager}/edit-manager', [UserManagementController::class, 'editManager'])->name('users.editManager');
    Route::put('/users/{manager}/update-manager', [UserManagementController::class, 'updateManager'])->name('users.updateManager');
    Route::delete('/users/{manager}/delete-manager', [UserManagementController::class, 'destroyManager'])->name('users.destroyManager');

    // کارمند
    Route::get('/users/{manager}/create-employee', [UserManagementController::class, 'createEmployee'])->name('users.createEmployee');
    Route::post('/users/{manager}/store-employee', [UserManagementController::class, 'storeEmployee'])->name('users.storeEmployee');
    Route::get('/users/{employee}/edit-employee', [UserManagementController::class, 'editEmployee'])->name('users.editEmployee');
    Route::put('/users/{employee}/update-employee', [UserManagementController::class, 'updateEmployee'])->name('users.updateEmployee');
    Route::delete('/users/{employee}/delete-employee', [UserManagementController::class, 'destroyEmployee'])->name('users.destroyEmployee');
});


Route::prefix('admin')->name('admin.')->middleware('role:Admin')->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');

    Route::get('/users/create-manager', [UserManagementController::class, 'createManager'])->name('users.createManager');
    Route::post('/users/store-manager', [UserManagementController::class, 'storeManager'])->name('users.storeManager');

    Route::get('/users/{manager}/create-employee', [UserManagementController::class, 'createEmployee'])->name('users.createEmployee');
    Route::post('/users/{manager}/store-employee', [UserManagementController::class, 'storeEmployee'])->name('users.storeEmployee');
});


Route::middleware(['auth'])
    ->prefix('admin/customers')
    ->name('admin.customers.')
    ->group(function () {
        Route::post('{customer}/notes', [CustomerNotesController::class, 'store'])->name('notes.store');
        Route::patch('notes/{note}', [CustomerNotesController::class, 'updateInline'])->name('notes.update');
        Route::delete('notes/{note}', [CustomerNotesController::class, 'destroyInline'])->name('notes.destroy');
    });

// Marketer
Route::middleware(['role:Marketer'])->group(function(){
    Route::get('tasks/today', [TaskController::class, 'today'])->name('tasks.today');
    Route::patch('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
});
Route::patch('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete')->middleware('role:Marketer');


Route::prefix('admin')->name('admin.')->middleware(['auth','role:Admin'])->group(function () {
    Route::post('users/{user}/block', [UserBlockController::class, 'block'])->name('users.block');
    Route::post('users/{user}/unblock', [UserBlockController::class, 'unblock'])->name('users.unblock');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth','verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class,'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class,'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class,'destroy'])->name('profile.destroy');

    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::patch('/announcements/{announcement}/toggle-active', [AnnouncementController::class, 'toggleActive'])->name('announcements.toggleActive');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
});
Route::get('/admin/sync-user-products', [AdminController::class, 'syncUserProducts'])->name('admin.syncUserProducts');
// فرم ویرایش محصول
Route::get('/admin/products/{product}/edit', [AdminController::class, 'editProduct'])->name('admin.products.edit');

// آپدیت محصول

use App\Http\Controllers\Admin\ActivityLogController;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activityLogs.index');
});
Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('admin.activity_logs.index');
Route::patch('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
Route::post('/notifications/seen-all', function () {
    \App\Models\Notification::where('user_id', auth()->id())
        ->where('seen', false)
        ->update(['seen' => true]);
    return response()->json(['success' => true]);
})->name('notifications.markAllSeen');

// ------------------------------
// مسیرهای مربوط به نقش Admin
// ------------------------------
Route::middleware(['auth','role:Admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function() {

        Route::resource('customers', CustomerController::class);

        Route::resource('marketers', MarketerController::class);

        Route::resource('guests', GuestController::class);

        Route::resource('products', ProductController::class);

        Route::resource('categories', CategoryController::class);

        Route::resource('referenceType', ReferenceTypeController::class);


        Route::get('marketers/{marketer}/customers', [CustomerController::class, 'customersOfMarketer'])
            ->name('marketers.customers.index');
        Route::get('marketers/{marketer}/customers/export/excel', [CustomerController::class, 'exportExcel'])
            ->name('marketers.customers.export.excel');
//        Route::get('marketers/{marketer}/customers/{customer}/', [CustomerController::class, 'show'])
//            ->name('marketers.customers.show');
        Route::get('marketers/{marketer}/customers/create', [CustomerController::class, 'create'])
            ->name('marketers.customers.create');
        Route::post('marketers/{marketer}/customers', [CustomerController::class, 'store'])
            ->name('marketers.customers.store');
        Route::get('marketers/{marketer}/customers/{customer}/edit', [CustomerController::class, 'edit'])
            ->name('marketers.customers.edit');
        Route::put('marketers/{marketer}/customers/{customer}', [CustomerController::class, 'update'])
            ->name('marketers.customers.update');
        Route::delete('marketers/{marketer}/customers/{customer}', [CustomerController::class, 'destroy'])
            ->name('marketers.customers.destroy');

        Route::get('customers/notes', [CustomerController::class, 'allNotes'])
            ->name('customers.notes');

        Route::get('marketers/{marketer}/customers/{customer}/invoices', [InvoiceController::class, 'indexByMarketer'])
            ->name('marketers.invoices.index');
        Route::get('marketers/{marketer}/customers/{customer}/invoices/create', [InvoiceController::class, 'createForAdmin'])
            ->name('marketers.invoices.create');
        Route::post('marketers/{marketer}/customers/{customer}/invoices', [InvoiceController::class, 'storeForAdmin'])
            ->name('marketers.invoices.store');
        Route::get('marketers/{marketer}/customers/{customer}/invoices/{invoice}', [InvoiceController::class, 'showByAdmin']
        )->name('marketers.invoices.show');
        Route::get('marketers/{marketer}/customers/{customer}/invoices/{invoice}/edit', [InvoiceController::class, 'editByAdmin'])
            ->name('marketers.invoices.edit');
        Route::put('marketers/{marketer}/customers/{customer}/invoices/{invoice}', [InvoiceController::class, 'updateByAdmin'])
            ->name('marketers.invoices.update');
        Route::delete('marketers/{marketer}/customers/{customer}/invoices/{invoice}', [InvoiceController::class, 'destroyByAdmin'])
            ->name('marketers.invoices.destroy');

        Route::get('reports/{user}', [ReportController::class,'index'])
            ->name('reports.index');

        Route::get('reports/create/{user}', [ReportController::class,'create'])
            ->name('reports.create');
        Route::post('reports/{user}', [ReportController::class,'store'])
            ->name('reports.store');

        Route::put('reports/{report}/feedback/{user}', [ReportController::class,'feedback'])
            ->name('reports.feedback');
        Route::get('reports/{report}/show/{user}', [ReportController::class,'show'])
            ->name('reports.show');
        Route::get('reports/{report}/edit/{user}', [ReportController::class,'edit'])
            ->name('reports.edit');
        Route::put('reports/{report}/update/{user}', [ReportController::class,'update'])
            ->name('reports.update');
        Route::delete('reports/{report}/destroy/{user}', [ReportController::class,'destroy'])
            ->name('reports.destroy');

        Route::get('marketers/{marketer}/customers/{customer}/notes', [CustomerNotesController::class, 'index'])
            ->name('marketers.customers.notes.index');
        Route::get('marketers/{marketer}/customers/{customer}/notes/create', [CustomerNotesController::class, 'create'])
            ->name('marketers.customers.notes.create');
        Route::post('marketers/{marketer}/customers/{customer}/notes', [CustomerNotesController::class, 'store'])
            ->name('marketers.customers.notes.store');
        Route::get('marketers/{marketer}/customers/{customer}/notes/{note}', [CustomerNotesController::class, 'show']
        )->name('marketers.customers.notes.show');
        Route::get('marketers/{marketer}/customers/{customer}/notes/{note}/edit', [CustomerNotesController::class, 'edit'])
            ->name('marketers.customers.notes.edit');
        Route::put('marketers/{marketer}/customers/{customer}/notes/{note}', [CustomerNotesController::class, 'update'])
            ->name('marketers.customers.notes.update');
        Route::delete('marketers/{marketer}/customers/{customer}/notes/{note}', [CustomerNotesController::class, 'destroy'])
            ->name('marketers.customers.notes.destroy');


            Route::get('commissions', [AdminController::class, 'index'])->name('admin.commissions');
            Route::put('update-sales/{userProduct}', [AdminController::class, 'updateSales'])->name('admin.updateSales');


    });
    Route::get('/admin/commissions', [AdminController::class, 'index'])->name('admin.commissions');

    // آپدیت فروش کاربر برای یک محصول
    Route::put('/admin/update-sales/{userProduct}', [AdminController::class, 'updateSales'])->name('admin.updateSales');
    // ساخت محصول جدید
Route::get('/admin/products/create', [AdminController::class, 'createProduct'])->name('admin.products.create');
Route::post('/admin/products/store', [AdminController::class, 'storeProduct'])->name('admin.products.store');

// ------------------------------
// مسیرهای مربوط به نقش Guest
// ------------------------------
Route::middleware(['auth','role:User|Manager|Admin'])
    ->prefix('user')
    ->name('user.')
    ->group(function() {
        Route::get('reports/{report}/submit', [ReportController::class, 'submit'])
            ->name('reports.submit');
           Route::put('reports/{report}/feedback/', [ReportController::class,'feedback'])
            ->name('reports.feedback');
        Route::get('reports/create', [ReportController::class,'create'])
            ->name('reports.create');
        Route::post('reports', [ReportController::class,'store'])
            ->name('reports.store');
        Route::get('reports', [ReportController::class,'index'])
            ->name('reports.index');
        Route::get('reports/show/{report}', [ReportController::class,'show'])
            ->name('reports.show');
        Route::get('reports/{report}/edit', [ReportController::class,'edit'])
            ->name('reports.edit');
        Route::put('reports/{report}', [ReportController::class,'update'])
            ->name('reports.update');
        Route::delete('reports/{report}/destroy', [ReportController::class,'destroy'])
            ->name('reports.destroy');
    });
    Route::get('reportsManagment', [ReportController::class, 'reportsManagment'])->name('user.reports.reportsManagment');
// ------------------------------
// مسیرهای مربوط به نقش Marketer
// ------------------------------
Route::middleware(['auth','role:Marketer'])
    ->prefix('marketer')
    ->name('marketer.')
    ->group(function() {
        Route::get('customers', [CustomerController::class,'index'])
            ->name('customers.index');
        Route::get('customers/export/excel', [CustomerController::class,'exportExcel'])
            ->name('customers.export.excel');
        Route::get('customers/create', [CustomerController::class,'create'])
            ->name('customers.create');
        Route::post('customers', [CustomerController::class,'store'])
            ->name('customers.store');
        Route::get('customers/{customer}', [CustomerController::class,'show'])
            ->name('customers.show');
        Route::get('customers/{customer}/edit', [CustomerController::class,'edit'])
            ->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class,'update'])
            ->name('customers.update');
        Route::get('customers/{customer}/invoices', [InvoiceController::class,'index'])
            ->name('invoices.index');
        Route::get('customers/{customer}/invoices/create', [InvoiceController::class,'create'])
            ->name('invoices.create');
        Route::post('customers/{customer}/invoices', [InvoiceController::class,'store'])
            ->name('invoices.store');
        Route::get('customers/{customer}/invoices/{invoice}', [InvoiceController::class,'show'])
            ->name('invoices.show');
        Route::get('customers/{customer}/invoices/{invoice}/edit', [InvoiceController::class,'edit'])
            ->name('invoices.edit');
        Route::put('customers/{customer}/invoices/{invoice}', [InvoiceController::class,'update'])
            ->name('invoices.update');
        Route::delete('customers/{customer}/invoices/{invoice}', [InvoiceController::class,'destroy'])
            ->name('invoices.destroy');


       // Route::get('reports/create', [ReportController::class, 'create'])
        //    ->name('reports.create');
      //  Route::get('reports/show/{report}', [ReportController::class, 'show'])
       //     ->name('reports.show');
       // Route::get('reports/{report}/edit', [ReportController::class, 'edit'])
      //      ->name('reports.edit');
      //  Route::get('reports/{report}/submit', [ReportController::class, 'submit'])
      //      ->name('reports.submit');
      //  Route::get('reports', [ReportController::class, 'index'])
      //      ->name('reports.index');
      //  Route::post('reports', [ReportController::class, 'store'])
      //      ->name('reports.store');
      //  Route::put('reports/{report}', [ReportController::class, 'update'])
      //      ->name('reports.update');
//Route::delete('reports/{report}/destroy', [ReportController::class, 'destroy'])
     //       ->name('reports.destroy');


        Route::get('customers/{customer}/notes', [CustomerNotesController::class, 'index'])
            ->name('customer.notes.index');
        Route::get('customers/{customer}/notes/create', [CustomerNotesController::class, 'create'])
            ->name('customer.notes.create');
        Route::post('customers/{customer}/notes', [CustomerNotesController::class, 'store'])
            ->name('customer.notes.store');
        Route::get('customers/{customer}/notes/{note}', [CustomerNotesController::class, 'show']
        )->name('customer.notes.show');
        Route::get('customers/{customer}/notes/{note}/edit', [CustomerNotesController::class, 'edit'])
            ->name('customer.notes.edit');
        Route::put('customers/{customer}/notes/{note}', [CustomerNotesController::class, 'update'])
            ->name('customer.notes.update');
        Route::delete('customers/{customer}/notes/{note}', [CustomerNotesController::class, 'destroy'])
            ->name('customer.notes.destroy');

            Route::get('sales', [UserPanelController::class, 'index'])
            ->name('sales.index');


    });


require __DIR__.'/auth.php';
Route::put('/admin/products/{product}', [AdminController::class, 'updateProduct'])->name('admin.products.update2');

Route::prefix('admin')->group(function() {
    Route::get('/panel', [AdminController::class, 'index'])->name('admin.products.index');
    Route::put('/user-products/{userProduct}', [AdminController::class, 'updateSales'])->name('admin.updateSales');
 Route::get('/products', [AdminController::class, 'products'])->name('admin.products.products');
 //Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('admin.products.update2');
});

Route::prefix('admin')->group(function() {
    Route::get('/customersedit/{customer}', [CustomerAdminController::class, 'edit'])
    ->name('admin.customersedit.edit');
  
Route::put('/customersupdate/{customer}', [CustomerAdminController::class, 'update'])
    ->name('admin.customersupdate.update');
    Route::get('/customersdelete/{customer}', [CustomerAdminController::class, 'destroy'])
    ->name('admin.customersdelete.destroy');
    Route::delete('customersdelete/{customer}', [CustomerAdminController::class, 'destroy'])
    ->name('admin.customersdelete.destroy');


});
  Route::get('/customersCreate', [CustomerAdminController::class, 'create'])
    ->name('admin.customersCreate.create');
    Route::post('/customersCreate', [CustomerAdminController::class, 'store'])
    ->name('admin.customersCreate.store');
// پنل کاربر
Route::middleware('auth')->group(function() {
    Route::get('/user/panel', [UserPanelController::class, 'index'])->name('user.panel');
});
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('customersAdmin/export/excel', [\App\Http\Controllers\Admin\CustomerAdminController::class, 'exportExcelAllCustomers'])
        ->name('customersAdmin.export.excel');
    Route::resource('customersAdmin', \App\Http\Controllers\Admin\CustomerAdminController::class);
});
Route::prefix('')->name('')->middleware(['auth','role:marketer'])->group(function () {
    Route::resource('customersAdmin', \App\Http\Controllers\Admin\CustomerAdminController::class);
});
Route::delete('/customers/{customer}/invoices/{invoice}', 
    [InvoiceController::class, 'destroy']
)->name('marketer.invoices.destroy');

  Route::get('/customersAdmin2', [\App\Http\Controllers\Admin\CustomerAdminController::class, 'index'])
    ->name('customersAdmin2.index');

Route::middleware(['auth'])->group(function () {
    Route::get('/messages',            [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages/groups',    [MessageController::class, 'storeGroup'])->name('messages.groups.store');
    Route::get('/messages/groups/{group}', [MessageController::class, 'showGroup'])->name('messages.groups.show');
    Route::post('/messages/groups/{group}/reply', [MessageController::class, 'replyGroup'])->name('messages.groups.reply');
    Route::get('/messages/groups/file/{groupMessage}', [MessageController::class, 'downloadGroupAttachment'])->name('messages.groups.download');
    Route::get('/messages/{user}',     [MessageController::class, 'show'])->name('messages.show'); // {user} = other user id
    Route::post('/messages/{user}/seen', [MessageController::class, 'markSeen'])->name('messages.markSeen');
    Route::post('/messages',           [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{user}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::get('/messages/file/{message}', [MessageController::class, 'download'])->name('messages.download');
});

Route::post('/admin/users/{id}/reset-password', [UserManagementController::class, 'resetPassword'])
    ->name('admin.users.resetPassword');
Route::get('/password/change', [UserManagementController::class, 'showChangeForm'])->name('password.change.form');
Route::post('/password/change', [UserManagementController::class, 'change'])->name('password.change');

Route::post('admin/users/{user}/update-roles', [UserManagementController::class, 'updateRoles'])->name('admin.users.updateRoles');
use App\Http\Controllers\ProductControllerWeb;



Route::get('/products', [ProductControllerWeb::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductControllerWeb::class, 'show'])->name('products.show');


use App\Http\Controllers\Admin\EvaluationFormController;

Route::middleware(['auth','role:Admin'])->prefix('admin')->name('admin.')->group(function(){

    // نمایش لیست فرم‌های ارزیابی
    Route::get('evaluations/forms', [EvaluationFormController::class, 'index'])
        ->name('evaluations.forms.index');

    // فرم ایجاد فرم جدید
    Route::get('evaluations/forms/create', [EvaluationFormController::class, 'create'])
        ->name('evaluations.forms.create');

    // ذخیره فرم جدید
    Route::post('evaluations/forms', [EvaluationFormController::class, 'store'])
        ->name('evaluations.forms.store');

    // نمایش یک فرم خاص
    Route::get('evaluations/forms/{form}', [EvaluationFormController::class, 'show'])
        ->name('evaluations.forms.show');

    // فرم ویرایش فرم
    Route::get('evaluations/forms/{form}/edit', [EvaluationFormController::class, 'edit'])
        ->name('evaluations.forms.edit');

    // بروزرسانی فرم
    Route::put('evaluations/forms/{form}', [EvaluationFormController::class, 'update'])
        ->name('evaluations.forms.update');

    // حذف فرم
    Route::delete('evaluations/forms/{form}', [EvaluationFormController::class, 'destroy'])
        ->name('evaluations.forms.destroy');

    // اضافه کردن سوال به فرم
    Route::post('evaluations/forms/{form}/questions', [EvaluationFormController::class,'addQuestion'])
        ->name('evaluations.forms.addQuestion');

    // حذف یک سوال از فرم
    Route::delete('evaluations/questions/{question}', [EvaluationFormController::class,'deleteQuestion'])
        ->name('evaluations.questions.delete');
});


use App\Http\Controllers\EvaluationController;

Route::middleware(['auth'])->group(function(){
    Route::get('/evaluations',[EvaluationController::class,'index'])->name('evaluations.index');

    Route::get('/evaluations/{target}',[EvaluationController::class,'evaluate'])->name('evaluations.evaluate');
    Route::post('/evaluations/{target}',[EvaluationController::class,'store'])->name('evaluations.store');
});
// routes/web.php
use App\Http\Controllers\Admin\MonthlyEvaluationController;

Route::middleware(['auth','role:Admin'])->prefix('admin')->name('admin.')->group(function(){
    Route::get('evaluations/monthly', [MonthlyEvaluationController::class,'index'])
        ->name('evaluations.monthly');
});
Route::delete('admin/evaluations/forms/{form}', [EvaluationFormController::class,'destroy'])
    ->name('admin.evaluations.forms.destroy');
// routes/web.php
Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy'])
    ->name('leaves.destroy');
use App\Http\Controllers\RequestTicketController;


// لیست/ساخت/ویرایش/حذف
Route::resource('requests', RequestTicketController::class)
    ->parameters(['requests' => 'requestTicket'])
    ->names('requests');

// تایید و رد
Route::patch('requests/{requestTicket}/approve', [RequestTicketController::class, 'approve'])->name('requests.approve');
Route::patch('requests/{requestTicket}/reject', [RequestTicketController::class, 'reject'])->name('requests.reject');
Route::get('requests/{requestTicket}/print', [RequestTicketController::class, 'printView'])->name('requests.print');

Route::get('/admin/reports', [App\Http\Controllers\AdminController::class, 'reports'])
    ->name('admin.reports')
    ->middleware(['auth', 'role:Admin|Manager']);
use App\Http\Controllers\MarketerOrderController;


    Route::get('/marketer/orders/create', [MarketerOrderController::class, 'create'])->name('marketer.orders.create');
    Route::post('/marketer/orders', [MarketerOrderController::class, 'store'])->name('marketer.orders.store');

// routes/api.php
// routes/api.php
Route::get('/products/search', function (Illuminate\Http\Request $request) {
    $q = $request->q;
    return \App\Models\Product::where('name', 'like', "%$q%")
             ->select('id', 'name', 'price')
             ->limit(10)
             ->get();
});

// routes/web.php
Route::get('/customers/find', [MarketerOrderController::class, 'findCustomer'])
    ->name('customers.find'); // بدون ->middleware('auth')


use App\Models\EmbedToken;
use Illuminate\Http\Request;

Route::get('/embed/order-create', [MarketerOrderController::class, 'embedCreate'])
    ->name('marketer.orders.embed');

Route::middleware(['auth', 'role:customer_review|Admin|internalManager|InternalManager'])->group(function () {
    Route::get('customer-satisfaction-forms', [CustomerSatisfactionFormController::class, 'index'])
        ->name('customer-satisfaction-forms.index');
    Route::get('customer-satisfaction-forms/create', [CustomerSatisfactionFormController::class, 'create'])
        ->name('customer-satisfaction-forms.create');
    Route::post('customer-satisfaction-forms', [CustomerSatisfactionFormController::class, 'store'])
        ->name('customer-satisfaction-forms.store');
    Route::get('customer-satisfaction-forms/{customerSatisfactionForm}', [CustomerSatisfactionFormController::class, 'show'])
        ->name('customer-satisfaction-forms.show');
    Route::delete('customer-satisfaction-forms/{customerSatisfactionForm}', [CustomerSatisfactionFormController::class, 'destroy'])
        ->name('customer-satisfaction-forms.destroy');
    Route::patch('customer-satisfaction-forms/{customerSatisfactionForm}/submit-result', [CustomerSatisfactionFormController::class, 'submitResult'])
        ->name('customer-satisfaction-forms.submit-result');
    Route::post('customer-satisfaction-forms/mark-assigned-seen', [CustomerSatisfactionFormController::class, 'markAssignedReferralsSeen'])
        ->name('customer-satisfaction-forms.mark-assigned-seen');
});

Route::post('/embed/order-store', [MarketerOrderController::class, 'embedStore'])
    ->name('marketer.orders.embed.store');
use App\Http\Controllers\FormController;

Route::view('/contact/success', 'contact_success')->name('contact.success');

// ✅ فرم: visitor فقط عدد باشد
Route::get('/contact/{visitor}', [FormController::class, 'show'])
    ->whereNumber('visitor')
    ->name('contact.form');

// ✅ ارسال فرم
Route::post('/contact/{visitor}', [FormController::class, 'submit'])
    ->whereNumber('visitor')
    ->name('contact.submit');


// --- admin ---
Route::get('/admin/contacts', [FormController::class, 'list'])->name('admin.contacts')-> middleware(['auth','role:Sales']);;

Route::delete('/admin/contacts/{id}', [FormController::class, 'delete'])
    ->name('contacts.delete');

Route::get('/admin/contacts/export', [FormController::class, 'exportCsv'])
    ->name('contacts.export');


Route::get('/leaves/export/csv', [LeaveController::class, 'exportCsv'])->name('leaves.export.csv');


Route::post('/admin/access-code', function (\Illuminate\Http\Request $request) {

    $code = $request->input('code');
    $codes = config('access_codes');

    if (!isset($codes[$code])) {
        return back()->withErrors(['code' => 'کد نامعتبر است']);
    }

    session([
        'access_code' => $code,
        'allowed_visitor' => $codes[$code]['visitor_name'],
    ]);

    return back()->with('success', 'دسترسی فعال شد');
})->name('admin.access.code');

use App\Http\Controllers\ContactController;

Route::get('/admin/contacts/{contact}/edit', [FormController::class, 'edit'])
    ->middleware('visitor.access')
    ->name('contacts.edit');

Route::put('/admin/contacts/{contact}', [FormController::class, 'update'])
    ->middleware('visitor.access')
    ->name('contacts.update');


    Route::get('c', [FormController::class, 'list2'])->name('c');

Route::delete('/admin/contacts/{id}', [FormController::class, 'delete'])
    ->name('contacts.delete');

Route::get('/admin/contacts/export', [FormController::class, 'exportCsv'])
    ->name('contacts.export');




// Draft در CRM
Route::prefix('crm/orders')->name('crm.orders.')->group(function () {
    Route::post('/draft', [MarketerOrderController::class, 'saveDraft'])->name('draft.save');
    Route::get('/{uuid}/edit', [MarketerOrderController::class, 'editDraft'])->name('draft.edit');
    Route::put('/{uuid}', [MarketerOrderController::class, 'updateDraft'])->name('draft.update');
    Route::post('/{uuid}/submit', [MarketerOrderController::class, 'submitDraft'])->name('draft.submit');
});





Route::middleware(['auth'])->group(function () {

    // ✅ لیست پیش‌نویس‌ها
    Route::get('/crm/orders/drafts', [MarketerOrderController::class, 'draftIndex'])
        ->name('crm.orders.draft.index');

    // ✅ گرفتن قفل (وقتی صفحه ادیت باز می‌شود)
    Route::post('/crm/orders/drafts/{uuid}/lock', [MarketerOrderController::class, 'draftAcquireLock'])
        ->name('crm.orders.draft.lock');

    // ✅ تمدید قفل (heartbeat)
    Route::post('/crm/orders/drafts/{uuid}/heartbeat', [MarketerOrderController::class, 'draftHeartbeat'])
        ->name('crm.orders.draft.heartbeat');

    // ✅ آزاد کردن قفل
    Route::post('/crm/orders/drafts/{uuid}/unlock', [MarketerOrderController::class, 'draftReleaseLock'])
        ->name('crm.orders.draft.unlock');

    // ✅ ادیت پیش‌نویس (همان قبلی)
    Route::get('/crm/orders/drafts/{uuid}/edit', [MarketerOrderController::class, 'editDraft'])
        ->name('crm.orders.draft.edit');
});
// routes/web.php
Route::get('/marketer/orders/embed/products-excel', [MarketerOrderController::class, 'exportProductsExcel'])
    ->name('marketer.orders.embed.products.excel');
// routes/web.php
Route::get('/marketer/products-excel', [MarketerOrderController::class, 'exportProductsExcel'])
  ->name('marketer.products.excel');
  
use App\Http\Controllers\PublicProductsController;

Route::get('/public/products', [PublicProductsController::class, 'index']);
Route::get('/public/products/{ariya_id}', [PublicProductsController::class, 'show']);
