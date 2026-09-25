                    <div class="modal fade" id="rolesModal{{ $employee->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content border-0 shadow rounded-4 users-modal">
                                <div class="modal-header">
                                    <h5 class="modal-title">مدیریت نقش‌ها: {{ $employee->name }}</h5>
                                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <form action="{{ route('admin.users.updateRoles', $employee->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                        <p class="text-muted small mb-3">نقش‌های موردنظر را انتخاب کنید.</p>

                                        <div class="row g-2">
                                            @foreach($roles as $role)
                                                <div class="col-12 col-sm-6">
                                                    <label class="role-check">
                                                        <input
                                                            type="checkbox"
                                                            name="roles[]"
                                                            value="{{ $role->name }}"
                                                            @if($employee->roles->contains('name', $role->name)) checked @endif
                                                        >
                                                        <span>{{ $role->name }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                            بستن
                                        </button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                                            ذخیره
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="deleteEmployeeModal{{ $employee->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow rounded-4 users-modal">
                                <div class="modal-header">
                                    <h5 class="modal-title">حذف کارمند</h5>
                                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    آیا از حذف <strong>{{ $employee->name }}</strong> مطمئن هستید؟
                                </div>

                                <div class="modal-footer">
                                    @include('admin.users.partials.active-toggle', ['user' => $employee])
                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                        انصراف
                                    </button>

                                    <form action="{{ route('admin.users.destroyEmployee', $employee->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger rounded-pill px-4">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="resetEmployeeModal{{ $employee->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow rounded-4 users-modal">
                                <div class="modal-header">
                                    <h5 class="modal-title">ریست پسورد</h5>
                                    <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    پسورد <strong>{{ $employee->name }}</strong> ریست شود؟
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">
                                        انصراف
                                    </button>

                                    <form action="{{ route('admin.users.resetPassword', $employee->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-info text-white rounded-pill px-4">
                                            ریست پسورد
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
