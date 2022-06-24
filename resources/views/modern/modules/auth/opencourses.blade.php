
@extends('layouts.default')

@if ($isInOpenCoursesMode)
    @push('head_styles')
        <link rel="stylesheet" type="text/css" href="{{ $urlAppend }}modules/course_metadata/course_metadata.css">
    @endpush
    @push('head_scripts')
        <script type="text/javascript">
            var dialog;

            var showMetadata = function(course) {
                $('.modal-body', dialog).load('anoninfo.php', {course: course}, function(response, status, xhr) {
                    if (status === "error") {
                        $('.modal-body', dialog).html("Sorry but there was an error, please try again");
                        //console.debug("jqxhr Request Failed, status: " + xhr.status + ", statusText: " + xhr.statusText);
                    }
                });
                dialog.modal('show');
            };

            $(document).ready(function() {
                dialog = $('<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modal-label" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">{!! trans("langCancel") !!}</span></button><h4 class="modal-title" id="modal-label">{!! trans('langCourseMetadata') !!}</h4></div><div class="modal-body">body</div></div></div></div>');
            });
        </script>
    @endpush
@endif

@section('content')

<div class="pb-3 pt-3">

    <div class="container-fluid main-container">

        <div class="row">

            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 justify-content-center ms-0 w-100 col_maincontent_active">
                    
                <div class="row p-5">
                                   
                    <nav class="navbar navbar-expand-lg navrbar_menu_btn">
                        <a type="button" id="getTopicButton" class="ms-2 btn btn-primary btn btn-primary" href="{{$urlAppend}}modules/help/help.php?language={{$language}}&topic={{$helpTopic}}&subtopic={{$helpSubTopic}}" style='margin-top:-10px'>
                            <i class="fas fa-question"></i>
                        </a>
                    </nav>
 
                    <nav class="navbar_breadcrumb pe-5" aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <?php $size_breadcrumb = count($breadcrumbs); $count=0; ?>
                            <?php for($i=0; $i<$size_breadcrumb; $i++){ ?>
                                <li class="breadcrumb-item"><a href="{!! $breadcrumbs[$i]['bread_href'] !!}">{!! $breadcrumbs[$i]['bread_text'] !!}</a></li>
                            <?php } ?> 
                        </ol>
                    </nav>

                    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="row p-2"></div><div class="row p-2"></div>
                        <legend class="float-none w-auto py-2 px-4 notes-legend"><span class="pos_TitleCourse"><i class="fas fa-folder-open" aria-hidden="true"></i> {{$toolName}} </span></legend>
                    </div>

                    <div class="row p-2"></div> <div class="row p-2"></div>

                    @if (isset($buildRoots))
                        {{ $buildRoots }}
                    @endif

                    
                    <div class='col-12'>
                        <ul class='list-group'>
                            <li class='list-group-item list-header'>{!! trans('langFaculty') !!}: <strong>{!! $tree->getFullPath($fc, false, $_SERVER['SCRIPT_NAME'] . '?fc=') !!}</strong>
                            {!! $childHTML !!}
                        </ul>
                    </div>
                    
                    @if (count($courses) > 0)
                    <div class="row p-2"></div>
                            <div class='col-12'>
                                <div class='table-responsive'>
                                    <table class='table opencourses_table' id="myopencourses_table">
                                        <thead class="opencourses_thead text-light">
                                            <tr class='list-header'>
                                                <th class='text-left'>{!! trans('langCourseCode') !!}</th>
                                            @if (isset($isInOpenCoursesMode))
                                                <th class='text-left' width='220'>{!! trans('langTeacher') !!}</th>
                                                <th width='30'>{!! trans('langOpenCoursesLevel') !!}</th>
                                            @else
                                                <th class='text-left' width='220'>{!! trans('langTeacher') !!}</th>
                                                <th width='30'>{!! trans('langType') !!}</th>
                                            @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($courses as $mycourse)
                                                <tr>
                                                    <td>
                                                    @if ($mycourse->visible == COURSE_OPEN)
                                                        <a href="../../courses/{!! urlencode($mycourse->k) !!}/">{!! $mycourse->i !!}</a>&nbsp;<small>({!! $mycourse->c !!})</small>
                                                    @else
                                                        {!! $mycourse->i !!}&nbsp;<small>({!! $mycourse->c !!})</small>
                                                    @endif
                                                    @if ($displayGuestLoginLinks)
                                                        @if ($course_data[$mycourse->id]['userguest'])
                                                            <div class='pull-right'>
                                                            @if ($course_data[$mycourse->id]['userguest']->password === '')
                                                                    <form method='post' action='{{ $urlAppend }}'>
                                                                        <input type='hidden' name='uname' value='{{ $course_data[$mycourse->id]['userguest']->username }}'>
                                                                        <input type='hidden' name='pass' value=''>
                                                                        <input type='hidden' name='next' value='/courses/{{ $mycourse->k }}/'>
                                                                        <button class='btn btn-default' type='submit' title='{!! trans('langGuestLogin') !!}' name='submit' data-toggle='tooltip'><span class='fa fa-plane'></span></button>
                                                                    </form>
                                                            @else
                                                                    <a class='btn btn-default' role='button' href='{{ $urlAppend }}main/login_form.php?user={!! urlencode($course_data[$mycourse->id]['userguest']->username) !!}&amp;next=%2Fcourses%2F{{ $mycourse->k }}%2F' title='{!! trans('langGuestLogin') !!}' data-toggle='tooltip'>
                                                                    <span class='fa fa-plane'></span></a>
                                                            @endif
                                                            </div>
                                                        @endif
                                                    @endif
                                                    </td>
                                                    <td>
                                                        {!! $mycourse->t !!}
                                                    </td>
                                                    <td class='text-center'>
                                                    @if ($isInOpenCoursesMode)
                                                        {!! CourseXMLElement::getLevel($mycourse->level) !!}&nbsp;
                                                        <a href='javascript:showMetadata("{!! $mycourse->k !!}");'><img src='{{ $themeimg }}/lom.png'/></a>
                                                    @else
                                                        @foreach( $icons as $visible=>$image)
                                                            @if($visible == $mycourse->visible)
                                                                {!! $image !!}
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        
                
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

                                

@endsection
    
    
