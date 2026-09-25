<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use Illuminate\Http\Request;

class EvaluationFormController extends Controller
{
    public function index()
    {
        $forms = EvaluationForm::with('questions')->paginate(10);
        return view('admin.evaluations.forms.index', compact('forms'));
    }

    public function create()
    {
        return view('admin.evaluations.forms.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'evaluator_role'  => 'required|string',
            'target_role'     => 'required|string',
            'department_role' => 'nullable|string',
            'unit_id'         => 'nullable|integer',
        ]);

        EvaluationForm::create($request->all());

        return redirect()->route('admin.evaluations.forms.index')
            ->with('success','فرم ارزیابی با موفقیت ایجاد شد.');
    }

    public function show(EvaluationForm $form)
    {
        $form->load('questions');
        return view('admin.evaluations.forms.show', compact('form'));
    }

    public function addQuestion(Request $request, EvaluationForm $form)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $form->questions()->create($request->only('title','description'));
        return back()->with('success','سوال اضافه شد.');
    }

    public function deleteQuestion(EvaluationQuestion $question)
    {
        $question->delete();
        return back()->with('success','سوال حذف شد.');
    }
    public function destroy(EvaluationForm $form)
{
    $form->questions()->delete(); // حذف سوالات مرتبط
    $form->delete();

    return redirect()->route('admin.evaluations.forms.index')->with('success','فرم ارزیابی حذف شد.');
}

}
