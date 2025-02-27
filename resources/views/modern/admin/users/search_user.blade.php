@extends('layouts.default')

@section('content')

<div class="pb-lg-3 pt-lg-3 pb-0 pt-0">

    <div class="container-fluid main-container">

        <div class="row rowMedium">

            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 justify-content-center col_maincontent_active_Homepage">
                    
                <div class="row p-lg-5 p-md-5 ps-1 pe-1 pt-5 pb-5">

                    @include('layouts.common.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

                    
                    @include('layouts.partials.legend_view',['is_editor' => $is_editor, 'course_code' => $course_code])
                    
                    @if(Session::has('message'))
                    <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-5'>
                        <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible fade show" role="alert">
                        {!! Session::get('message') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </p>
                    </div>
                    @endif

                    {!! isset($action_bar) ?  $action_bar : '' !!}
                    
                    <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
                        <div class='form-wrapper shadow-sm p-3 mt-5 rounded'>
                        
                        <form class='form-horizontal' role='form' action='listusers.php' method='get' name='user_search'>
                        <fieldset>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label for='uname' class='col-sm-6 control-label-notes'>{{ trans('langUsername') }}:</label>
                                <div class='col-sm-12'>
                                    <input class='form-control' type='text' name='uname' id='uname' value='{{ $uname }}'>
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label for='fname' class='col-sm-6 control-label-notes'>{{ trans('langName') }}:</label>
                                <div class='col-sm-12'>
                                    <input class='form-control' type='text' name='fname' id='fname' value='{{ $fname }}'>
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label for='lname' class='col-sm-6 control-label-notes'>{{ trans('langSurname') }}:</label>
                                <div class='col-sm-12'>
                                    <input class='form-control' type='text' name='lname' id='lname' value='{{ $lname }}'>
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label for='email' class='col-sm-6 control-label-notes'>{{ trans('langEmail') }}:</label>
                                <div class='col-sm-12'>
                                    <input class='form-control' type='text' name='email' id='email' value='{{ $email }}'>
                                </div>
                            </div>  
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label for='am' class='col-sm-6 control-label-notes'>{{ trans('langAm') }}:</label>
                                <div class='col-sm-12'>
                                    <input class='form-control' type='text' name='am' id='am' value='{{ $am }}'>
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label class='col-sm-6 control-label-notes'>{{ trans('langUserType') }}:</label>
                                <div class='col-sm-12'>
                                    {!! selection($usertype_data, 'user_type', 0, 'class="form-control"') !!}
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label class='col-sm-6 control-label-notes'>{{ trans('langAuthMethod') }}:</label>
                                <div class='col-sm-12'>
                                    {!! selection($authtype_data, 'auth_type', 0, 'class="form-control"') !!}
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label class='col-sm-6 control-label-notes'>{{ trans('langRegistrationDate') }}:</label>
                                
                                    <div class='col-sm-12'>
                                        {!! selection(['1' => trans('langAfter'), '2' => trans('langBefore')], 'reg_flag', $reg_flag, 'class="form-control"') !!}
                                    </div>
                                    <div class='mt-2 col-sm-12'>       
                                        <input class='form-control' name='user_registered_at' id='id_user_registered_at' type='text' value='{{ $user_registered_at }}' placeholder='{{ trans('langRegistrationDate') }}'>
                                    </div>   
                                
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label class='col-sm-6 control-label-notes'>{{ trans('langExpirationDate') }}:</label>
                                <div class='col-sm-12'>
                                    <input class='form-control' name='user_expires_until' id='id_user_expires_until' type='text' value='{{ $user_expires_until }}' data-date-format='dd-mm-yyyy' placeholder='{{ trans('langUntil') }}'>
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label class='col-sm-6 control-label-notes'>{{ trans('langEmailVerified') }}:</label>
                                <div class='col-sm-12'>
                                    {!! selection($verified_mail_data, 'verified_mail', $verified_mail, 'class="form-control"') !!}
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label for='dialog-set-value' class='col-sm-6 control-label-notes'>{{ trans('langFaculty') }}:</label>
                                <div class='col-sm-12'>
                                    {!! $html !!}
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <label for='search_type' class='col-sm-6 control-label-notes'>{{ trans('langSearchFor') }}:</label>
                                <div class='col-sm-12'>
                                    <select class='form-select' name='search_type' id='search_type'>
                                    <option value='exact'>{{ trans('langSearchExact') }}</option>
                                    <option value='begin'>{{ trans('langSearchStartsWith') }}</option>
                                    <option value='contains' selected>{{ trans('langSearchSubstring') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <div class='col-sm-10 col-sm-offset-2'>
                                    <div class='checkbox'>
                                    <label>
                                        <input type='checkbox' name='search' value='inactive'{{ $inactive_checked ? " checked" : "" }}>
                                        {{ trans('langInactiveUsers') }}
                                    </label>
                                    </div> 
                                </div>
                            </div>    
                            <div class='row p-2'></div>
                            <div class='form-group'>
                                <div class='col-sm-10 col-sm-offset-2'>
                                    <input class='btn btn-primary' type='submit' value='{{ trans('langSearch') }}'>
                                    <a class='btn btn-secondary' href='index.php'>{{ trans('langCancel') }}</a>
                                </div>
                            </div>
                        </fieldset>
                        </form>
                    </div></div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection