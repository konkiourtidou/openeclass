@extends('layouts.default_old')

@section('content')
    {!! isset($action_bar) ?  $action_bar : '' !!}
    <div class="col-xl-10 col-lg-10 col-md-12 col-sm-12 col-12 justify-content-center col_maincontent_active">

        <div class="row p-5">

            <nav class="navbar navbar-expand-lg navrbar_menu_btn">
                <button type="button" id="menu-btn" class="d-none d-sm-block d-sm-none d-md-block d-md-none d-lg-block btn btn-primary menu_btn_button">
                    <i class="fas fa-align-left"></i>
                    <span></span>
                </button>


                <a class="btn btn-primary d-lg-none mr-auto" type="button" data-bs-toggle="offcanvas" href="#collapseTools" role="button" aria-controls="collapseTools" style="margin-top:-10px;">
                    <i class="fas fa-tools"></i>
                </a>
            </nav>

            @include('layouts.common.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

            <div class="offcanvas offcanvas-start d-lg-none mr-auto" tabindex="-1" id="collapseTools" aria-labelledby="offcanvasExampleLabel">
                <div class="offcanvas-header">
                    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                @include('layouts.partials.sidebarAdmin')
                </div>
            </div>

            @if($breadcrumbs && count($breadcrumbs)>2)
                <div class='row p-2'></div>
                <div class="float-start">
                    <p class='control-label-notes'>{!! $breadcrumbs[1]['bread_text'] !!}</p>
                    <small class='text-secondary'>{!! $breadcrumbs[count($breadcrumbs)-1]['bread_text'] !!}</small>
                </div>
                <div class='row p-2'></div>
            @endif

            {!! isset($action_bar) ?  $action_bar : '' !!}

            @if(Session::has('message'))
                <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-5'>
                    <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible fade show" role="alert">
                        {{ Session::get('message') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </p>
                </div>
            @endif

            {!! $users_login_data !!}

            <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
                <div class='form-wrapper shadow-lg p-3 mb-5 bg-body rounded bg-primary'>
                    <form class='form-horizontal' role='form' method='get' action='{{ $_SERVER['SCRIPT_NAME'] }}'>
                        <input type="hidden" name="u" value="{{ $u }}">
                        <div class='form-group mt-3' data-date='{{ $user_date_start }}' data-date-format='dd-mm-yyyy'>
                            <label class='col-sm-6 control-label-notes'>{{ trans('langStartDate') }}:</label>
                            <div class='col-sm-12'>
                                <input class='form-control' name='user_date_start' id='user_date_start' type='text' value = '{{ $user_date_start }}'>
                            </div>
                        </div>
                        <div class='form-group mt-3' data-date= '{{ $user_date_end }}' data-date-format='dd-mm-yyyy'>
                            <label class='col-sm-6 control-label-notes'>{{ trans('langEndDate') }}:</label>
                            <div class='col-sm-12'>
                                <input class='form-control' name='user_date_end' id='user_date_start' type='text' value= '{{ $user_date_end }}'>
                            </div>
                        </div>
                        <div class='form-group mt-3'>
                            <label class='col-sm-6 control-label-notes'>{{ trans('langLogTypes') }}:</label>
                            <div class='col-sm-12'>{!! selection($log_types, 'logtype', $logtype, "class='form-control'") !!}</div>
                        </div>
                        <div class="form-group mt-3">
                            <label class="col-sm-6 control-label-notes">{{ trans('langCourse') }}:</label>
                            <div class="col-sm-12">{!! selection($cours_opts, 'u_course_id', $u_course_id, "class='form-control'") !!}</div>
                        </div>
                        <div class="form-group mt-3">
                            <label class="col-sm-6 control-label-notes">{{ trans('langLogModules') }}:</label>
                            <div class="col-sm-12">{!! selection($module_names, 'u_module_id', '', "class='form-control'") !!}</div>
                        </div>
                        <div class="form-group mt-3">
                            <div class="col-sm-10 col-sm-offset-9">
                                <input class="btn btn-primary" type="submit" name="submit" value="{{ trans('langSubmit') }}">
                                <a class="btn btn-secondary" href="listusers.php" data-placement="bottom" data-toggle="tooltip" title="" data-original-title="{{ trans('langBack') }}" >
                                    <span class="fa fa-reply space-after-icon"></span><span class="hidden-xs">{{ trans('langBack') }}</span>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
