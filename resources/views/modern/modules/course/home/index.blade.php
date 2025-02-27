@extends('layouts.default')

@section('content')

<div class="pb-lg-3 pt-lg-3 pb-0 pt-0">
    <div class="container-fluid main-container">
        <div class="row rowMedium">

            <div id="background-cheat-leftnav" class="col-xl-2 col-lg-3 col-md-0 col-sm-0 col-0 justify-content-center col_sidebar_active">
                <div class="d-none d-sm-block d-sm-none d-md-block d-md-none d-lg-block">
                    @include('layouts.partials.sidebar',['is_editor' => $is_editor])
                </div>
            </div>

            <div class="col-xl-10 col-lg-9 col-md-12 col-sm-12 col-12 justify-content-center col_maincontent_active">

                <div class="row p-lg-5 p-md-5 ps-1 pe-2 pt-5 pb-5">

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
                            @include('layouts.partials.sidebar',['is_editor' => $is_editor])
                        </div>
                    </div>

                    @include('layouts.partials.legend_view',['is_editor' => $is_editor, 'course_code' => $course_code])


                    @if(Session::has('message'))
                        <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-5'>
                            <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible fade show" role="alert">
                            {!! Session::get('message') !!}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </p>
                        </div>
                    @endif


                    <div class='col-sm-12'>
                        <div class='panel panel-default mt-3 col_maincontent_coursePage'>
                            <div class='panel-body borderBody'>
                                <div class='row'>
                                    <div class='col-12 pb-2'>
                                        <ul class="nav navbar navbar-left d-flex d-inline-flex float-end">
                                            <li class='nav-item d-inline-flex  align-items-center mr-2 ps-3 pe-3'>
                                                <a href='javascript:void(0);' data-bs-modal='citation' data-bs-toggle='modal' data-bs-target='#citation'>
                                                    <span class='fa fa-paperclip fa-fw' data-bs-toggle='tooltip' data-bs-placement='bottom' title data-bs-original-title="{{ trans('langCitation') }}"></span>
                                                </a>
                                            </li>
                                            @if($uid)
                                                @if ($is_course_admin)
                                                    <li class='nav-item d-inline-flex  align-items-center mr-2 ps-3 pe-3'>
                                                        <a href="{{ $urlAppend }}modules/user/index.php?course={{$course_code}}">
                                                            <span class="fa fa-users fa-fw" data-bs-toggle="tooltip" data-bs-placement="bottom" title data-bs-original-title="{{ $numUsers }}&nbsp;{{ trans('langRegistered') }}"></span>
                                                        </a>
                                                    </li>
                                                @else
                                                    @if ($visible == COURSE_CLOSED)
                                                        <li class='nav-item d-inline-flex  align-items-center mr-2 ps-3 pe-3'>
                                                            <a href="{{ $urlAppend }}modules/user/userslist.php?course={{ $course_code }}">
                                                                <span class="fa fa-users fa-fw" data-bs-toggle="tooltip" data-bs-placement="bottom" title data-bs-original-title="{{ $numUsers }}&nbsp;{{ trans('langRegistered') }}"></span>
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endif
                                            @endif
                                            @if ($offline_course)
                                                <li class='nav-item d-inline-flex  align-items-center mr-2 ps-3 pe-3'>
                                                    <a href="{{ $urlAppend }}modules/offline/index.php?course={{ $course_code }}">
                                                        <span class="fa fa-download fa-fw" data-bs-toggle="tooltip" data-bs-placement="bottom" title data-bs-original-title="{{ trans('langDownloadCourse') }}"></span>
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div><hr>
                                    @if($course_info->home_layout == 1)
                                        <div class='col-lg-6 col-12'>
                                            <figure>
                                                <picture>
                                                    @if($course_info->course_image)
                                                    <img class='uploadImageCourse' src='{{$urlAppend}}courses/{{$course_code}}/image/{{$course_info->course_image}}' alt='Course Banner'/>
                                                    @else
                                                    <img class='uploadImageCourse' src='{{$urlAppend}}template/modern/img/ph1.jpg'/>
                                                    @endif
                                                </picture>
                                            </figure>
                                        </div>
                                        <div class='col-lg-6 col-12'>
                                            <div class='course_info'>
                                                @if ($course_info->description)
                                                        {!! $course_info->description !!}
                                                @else
                                                    <p class='not_visible'> - {{ trans('langThisCourseDescriptionIsEmpty') }} - </p>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class='col-12'>
                                            <div class='course_info'>
                                                @if ($course_info->description)
                                                        <div class="control-label-notes">{{ trans('langDescription') }}</div>
                                                        {!! $course_info->description !!}
                                                @else
                                                    <p class='not_visible'> - {{ trans('langThisCourseDescriptionIsEmpty') }} - </p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                </div>

                                @if ((!$is_editor) and (!$courseDescriptionVisible))
                                    @if ($course_info->course_license)
                                        <span class='pull-right' style="margin-top: 15px;">{!! copyright_info($course_id) !!}</span>
                                    @endif
                                @else
                                    <div class='col-12 course-below-wrapper mt-5'>
                                        <div class='row text-muted course-below-info'>
                                            <div class="row">
                                                <div class="col-xl-5 col-lg-5 col-md-6 col-sm-12 col-12 d-flex justify-content-md-start justify-content-center">
                                                    <a role='button' id='btn-syllabus' data-bs-toggle='collapse' href='#collapseDescription' aria-expanded='false' aria-controls='collapseDescription'>
                                                        <span class='fa fa-chevron-right fa-fw'></span>
                                                        <span class='ps-1'>{{ trans('langCourseDescription') }}</span>
                                                    </a>
                                                </div>
                                                @if($is_editor)
                                                    <div class="col-xl-7 col-lg-7 col-md-6 col-sm-12 col-12 d-flex justify-content-md-end justify-content-center">
                                                        @if($courseDescriptionVisible>0)
                                                            <span class='float-md-end pe-md-0 mt-md-0 float-start pe-0 mt-5'>{{trans('langCourseDescription')}}</span>
                                                        @else
                                                            <span class='float-md-end pe-md-0 mt-md-0 float-start pe-0 mt-5'>{{trans('langAdd')}}</span>
                                                        @endif
                                                        <span class='float-md-end pe-md-0 mt-md-0 float-start pe-0 mt-5'>{!! $edit_course_desc_link !!}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            @if ($course_info->course_license)
                                                <span class="pull-right">{!! copyright_info($course_id) !!}</span>
                                            @endif
                                            <div class='col-12'>
                                                <div class='collapse shadow-sm p-3 bg-body rounded' id='collapseDescription'>
                                                    <div class='col-12'>
                                                        @foreach ($course_descriptions as $row)
                                                            <div class='row'>
                                                                <div class='col-xl-6 col-12'>
                                                                    <p class='control-label-notes text-start'>{{$row->title}}</p>
                                                                </div>
                                                                <div class='col-xl-6 col-12'>
                                                                    {!! standard_text_escape($row->comments) !!}
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            </div>


                            @if(isset($rating_content) || isset($social_content) || isset($comment_content)) 
                                <div class='panel-footer'>
                                    <div class='row'>
                                        @if(isset($rating_content))
                                            <div class='col-xl-4 col-lg-4 col-12 mt-lg-0 mt-4'>
                                                <div class='ps-2 d-flex justify-content-lg-start justify-content-center'>{!! $rating_content !!}</div>
                                            </div>
                                        @endif
                                        @if(isset($comment_content))
                                            <div class='col-xl-4 col-lg-3 col-12 mt-lg-0 mt-4'>
                                                <div class='d-flex justify-content-center'>{!! $comment_content !!}</div>
                                            </div>  
                                        @endif
                                        @if(isset($social_content))
                                            <div class='col-xl-4 col-lg-5 col-12 mt-lg-0 mt-4'>
                                                <div class='d-flex justify-content-lg-end justify-content-center pe-1'>{!! $social_content !!}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>

                    <div class="col-xl-8 col-lg-6 col-md-12 col_maincontent_unit mt-4">
                        @if (!$alter_layout)
                            <div class='panel panel-default'>
                                <div class='panel-heading ps-3 pe-3 pb-2 pt-2'>
                                    <div class='row'>
                                        <div class='col-sm-12'>
                                            <div class='control-label-notes text-center'>
                                                {{ trans('langCourseUnits') }}
                                            </div>
                                        </div>
                                        
                                        <div class='col-md-6 col-12 d-flex justify-content-md-start justify-content-center mt-md-0 mt-4'>
                                            @if ($is_editor and $course_info->view_type == 'units')
                                                <a href='{{ $urlServer }}modules/units/info.php?course={{ $course_code }}' class='add-unit-btn' data-bs-toggle='tooltip' data-bs-placement='bottom' title data-bs-original-title="{{ trans('langAddUnit') }}">
                                                    {{ trans('langAddUnit') }} <span class='fa fa-plus-circle'></span>
                                                </a>
                                            @endif 
                                        </div>
                                        
                                        <div class='col-md-6 col-12 d-flex justify-content-md-end justify-content-center mt-md-0 mt-4'>
                                            <a class='add-unit-btn' id='help-btn' href='{{ $urlAppend }}modules/help/help.php?language={{$language}}&topic=course_units' data-bs-toggle='tooltip' data-bs-placement='bottom' title data-bs-original-title="{{ trans('langHelp') }}">
                                                {{ trans('langHelp') }} <span class='fa fa-question-circle'></span>
                                            </a>
                                        </div>
                                    </div>
                                </div> 

                                <div class='panel-body'>
                                    <div class='' id='boxlistSort'>
                                        {!! $cunits_content !!}
                                    </div>
                                </div>
                                
                            </div>
                            {!! $course_home_main_area_widgets !!}
                        @else
                            <div class='panel panel-default'>
                                <div class='panel-heading ps-3 pe-3 pb-2 pt-2'>
                                    {{ trans('langCourseUnits') }}
                                </div>
                                <div class='panel-body'>
                                    <div class='not_visible text-center'> - {{ trans('langNoUnits') }} - </div>
                                </div>
                            </div>
                        @endif

                        <div class="panel panel-default mt-4">                            
                            <div class='panel-heading ps-3 pe-3 pb-2 pt-2'>
                                <div class='control-label-notes text-center'>{{ trans('langAnnouncements') }}</div>
                            </div>
                            <div class='panel-body'>
                                <ul class='tablelist'>
                                    {!! course_announcements() !!}
                                </ul>
                            </div>
                            <div class='panel-footer'>
                                <div class='text-end'>
                                    <a href='{{ $urlAppend }}modules/announcements/index.php?course={{ $course_code}}'>
                                        <small>{{ trans('langMore') }}&hellip;</small>
                                    </a>
                                </div>
                            </div>
                            {!! $course_home_sidebar_widgets !!}
                        </div>

                    </div><!-- end col units -->

                    <div class="col-xl-4 col-lg-6 col-md-12 mt-lg-4 mt-4 float-end ">

                        <div class="container-fluid container_fluid_calendar col_maincontent_active_calendar">
                            {!! $user_personal_calendar !!}
                            <div class='col-sm-12 mt-4 pb-2'>
                                <div class='row rowMedium'>
                                    <div class='col-sm-12 event-legend'>
                                        <span class='event event-important mt-1'></span>
                                        <span>{{ trans('langAgendaDueDay') }}</span>
                                    </div>
                                        
                                    <div class='col-sm-12 event-legend'>
                                        <span class='event event-info mt-1'></span>
                                        <span>{{ trans('langAgendaCourseEvent') }}</span>
                                    </div>

                                    <div class='col-sm-12 event-legend'>
                                        <span class='event event-success mt-1'></span>
                                        <span>{{ trans('langAgendaSystemEvent') }}</span>
                                    </div>
                                    <div class='col-sm-12 event-legend pb-3'>
                                        <span class='event event-special'></span>
                                        <span>{{ trans('langAgendaPersonalEvent') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        @if(isset($course_completion_id) and $course_completion_id > 0)
                            <div class="panel panel-default mt-4">
                                <div class='panel-heading ps-3 pe-3 pb-2 pt-2'>
                                    <div class='control-label-notes text-center'>{{ trans('langCourseCompletion') }}</div>
                                </div>
                                <div class='panel-body'>
                                    <div class='text-center'>
                                        <div class='col-sm-12'>
                                            <div class="center-block" style="display:inline-block;">
                                                <a style="text-decoration:none" href='{{ $urlServer }}modules/progress/index.php?course={{ $course_code }}&badge_id={{ $course_completion_id}}&u={{ $uid }}'>
                                            @if ($percentage == '100%')
                                                <i class='fa fa-check-circle fa-5x state_success'></i>
                                            @else
                                                <div class='course_completion_panel_percentage'>
                                                    {{ $percentage }}
                                                </div>
                                            @endif
                                            </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (isset($level) && !empty($level))
                            <div class='panel panel-default mt-4'>
                                <div class='panel-heading ps-3 pe-3 pb-2 pt-2'>
                                    <div class='control-label-notes text-center'>{{ trans('langOpenCourseShort') }}</div>
                                </div>
                                <div class='panel-body'>
                                    {!! $opencourses_level !!}
                                </div>
                                <div class='panel-footer'>
                                    {!! $opencourses_level_footer !!}
                                </div>
                            </div>
                        @endif
                        



                    </div><!-- end col calendar-announcements-progress -->

                
                </div> <!-- end row -->
               

            </div><!-- end col-10 maincontent active-->


        </div>
    </div>
</div>





<div class='modal fade' id='citation' tabindex='-1' role='dialog' aria-labelledby='myModalLabel' aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header'>
                <button type='button' class='close' data-bs-dismiss='modal' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                </button>
                <div class='modal-title h4' id='myModalLabel'>{{ trans('langCitation') }}</div>
            </div>
            <div class='modal-body'>
                {{ $course_info->prof_names }}&nbsp;
                <span>{{ $currentCourseName }}</span>&nbsp;
                {{ trans('langAccessed') }} {{ claro_format_locale_date(trans('dateFormatLong'), strtotime('now')) }}&nbsp;
                {{ trans('langFrom2') }} {{ $urlServer }}courses/{{$course_code}}/
            </div>
        </div>
    </div>
</div>

@if(!$registered)
    <script type='text/javascript'>
        $(function() {
            $('#passwordModal').on('click', function(e){
                var registerUrl = this.href;
                e.preventDefault();
                @if ($course_info->password !== '')
                    bootbox.dialog({
                        title: '{{ js_escape(trans('langLessonCode')) }}',
                        message: '<form class="form-horizontal" role="form" action="' + registerUrl + '" method="POST" id="password_form">' +
                                    '<div class="form-group">' +
                                        '<div class="col-sm-12">' +
                                            '<input type="text" class="form-control" id="password" name="password">' +
                                            '<input type="hidden" id="register" name="register" value="from-home">' +
                                            "{!! generate_csrf_token_form_field() !!}" +
                                        '</div>' +
                                    '</div>' +
                                '</form>',
                        buttons: {
                            cancel: {
                                label: '{{ js_escape(trans('langCancel')) }}',
                                className: 'btn-secondary'
                            },
                            success: {
                                label: '{{ js_escape(trans('langSubmit')) }}',
                                className: 'btn-success',
                                callback: function (d) {
                                    var password = $('#password').val();
                                    if(password != '') {
                                        $('#password_form').submit();
                                    } else {
                                        $('#password').closest('.form-group').addClass('has-error');
                                        $('#password').after('<span class="help-block">{{ js_escape(trans('langTheFieldIsRequired')) }}</span>');
                                        return false;
                                    }
                                }
                            }
                        }
                    });
                @else
                    $('<form method="POST" action="' + registerUrl + '">' +
                        '<input type="hidden" name="register" value="from-home">' +
                        "{!! generate_csrf_token_form_field() !!}" +
                    '</form>').appendTo('body').submit();
                @endif
            });
        });
    </script>


@endif

@endsection
