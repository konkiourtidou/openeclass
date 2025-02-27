@extends($is_in_tinymce ? 'layouts.embed' : 'layouts.default')

@section('content')
    {!! $actionBar !!}

    @if ($dialogBox)
        @include("modules.document.$dialogBox")
    @endif

    @if (count($fileInfo) or $curDirName)
        <div class='row'>
            <div class='col-md-12'>
                <div class='panel'>
                    <div class='panel-body'>
                        @if ($curDirName)
                            <div class='pull-right'>
                                <a href='{{ $parentLink }}' type='button' class='btn btn-success'>
                                    <span class='fa fa-level-up'></span>&nbsp;{{ trans('langUp') }}
                                </a>
                            </div>
                        @endif
                        <div>
                            {!! make_clickable_path($curDirPath) !!}
                            @if ($downloadPath)
                               &nbsp;&nbsp;{!! icon('fa-download', trans('langDownloadDir'), $downloadPath) !!}
                            @endif
                        </div>
                        @if ($curDirName and $dirComment)
                            <div>{{ $dirComment }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class='row'>
            <div class='col-md-12'>
                <div class='table-responsive'>
                    <table class='table-default'>
                        <tr class='list-header'>
                            <th class='text-left' width='60'>{!! headlink(trans('langType'), 'type') !!}</th>
                            <th class='text-left'>{!! headlink(trans('langName'), 'name') !!}</th>

                            <th class='text-left'>{{ trans('langSize') }}</th>
                            <th class='text-left'>{!! headlink(trans('langDate'), 'date') !!}</th>
                            @unless ($is_in_tinymce)
                                <th class='text-center'>{!! icon('fa-gears', trans('langCommands')) !!}</th>
                            @endif
                        </tr>

                        @forelse ($fileInfo as $file)

                            <tr class='{{ !$file->visible || ($file->extra_path && !$file->common_doc_visible) ? 'not_visible' : 'visible' }}'>
                                <td class='text-center'><span class='fa {{ $file->icon }}'></span></td>
                                <td>
                                    @if ($file->updated_message)
                                        <span class='btn btn-success pe-none mt-2 pull-right'>
                                            {{ $file->updated_message }}
                                        </span>
                                    @endif
                                    <input type='hidden' value='{{ $file->url }}'>
                                    @if ($file->is_dir)
                                        <a href='{{ $file->url }}'>{{ $file->filename }}</a>
                                    @else
                                        {!! $file->link !!}
                                    @endif
                                    @if ($can_upload)
                                        @if ($file->extra_path)
                                            @if ($file->common_doc_path)
                                                @if ($file->common_doc_visible)
                                                    {!! icon('common', trans('langCommonDocLink')) !!}
                                                @else
                                                    {!! icon('common-invisible', trans('langCommonDocLinkInvisible')) !!}
                                                @endif
                                            @else
                                                {!! icon('fa-external-link', trans('langExternalFile')) !!}
                                            @endif
                                        @endif
                                        @if (!$file->public)
                                            {!! icon('fa-lock', trans('langNonPublicFile')) !!}
                                        @endif
                                        @if ($file->editable)
                                            {!! icon('fa-edit', trans('langEdit'), $file->edit_url) !!}
                                        @endif
                                    @endif
                                    @if ($file->copyrighted)
                                        {!! icon($file->copyright_icon, $file->copyright_title, $file->copyright_link,
                                            'target="_blank" style="color:#555555;"') !!}
                                    @endif
                                    @if ($file->comment)
                                        <br>
                                        <span class='comment text-muted'>
                                            <small>
                                                {!! nl2br(e($file->comment)) !!}
                                            </small>
                                        </span>
                                    @endif
                                </td>
                                @if ($file->is_dir)
                                    <td>&nbsp;</td>
                                    <td class='center'>{{ $file->date }}</td>
                                @elseif ($file->format == '.meta')
                                    <td>{{ $file->size }}</td>
                                    <td class='center'>{{ $file->date }}</td>
                                @else
                                    <td>{{ $file->size }}</td>
                                    <td title='{{ nice_format($file->date, true) }}' class='center'>{{ nice_format($file->date, true, true) }}</td>
                                @endif
                                @unless ($is_in_tinymce)
                                    <td class='{{ $can_upload? 'option-btn-cell': 'text-center'}}'>
                                        {!! $file->action_button !!}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan='5'>
                                    <p class='not_visible text-center'> - {{ trans('langNoDocuments') }} - </p>
                                </td>
                            </tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class='alert alert-warning'>{{ trans('langNoDocuments') }}</div>
    @endif

    <script>
    $(function(){
        $('.fileModal').click(function (e)
        {
            e.preventDefault();
            var fileURL = $(this).attr('href');
            var downloadURL = $(this).prev('input').val();
            var fileTitle = $(this).attr('title');

            // BUTTONS declare
            var bts = {
                download: {
                    label: '<span class="fa fa-download"></span> {{ trans('langDownload') }}',
                    className: 'btn-success',
                    callback: function (d) {
                        window.location = downloadURL;
                    }
                },
                print: {
                    label: '<span class="fa fa-print"></span> {{ trans('langPrint') }}',
                    className: 'btn-primary',
                    callback: function (d) {
                        var iframe = document.getElementById('fileFrame');
                        iframe.contentWindow.print();
                    }
                }
            };
            if (screenfull.enabled) {
                bts.fullscreen = {
                    label: '<span class="fa fa-arrows-alt"></span> {{ trans('langFullScreen') }}',
                    className: 'btn-primary',
                    callback: function() {
                        screenfull.request(document.getElementById('fileFrame'));
                        return false;
                    }
                };
            }
            bts.newtab = {
                label: '<span class="fa fa-plus"></span> {{ trans('langNewTab') }}',
                className: 'btn-primary',
                callback: function() {
                    window.open(fileURL);
                    return false;
                }
            };
            bts.cancel = {
                label: '{{ trans('langCancel') }}',
                className: 'btn-default'
            };

            bootbox.dialog({
                size: 'large',
                title: fileTitle,
                message: '<div class="row">'+
                            '<div class="col-sm-12">'+
                                '<div class="iframe-container"><iframe id="fileFrame" src="'+fileURL+'"></iframe></div>'+
                            '</div>'+
                        '</div>',
                buttons: bts
            });
        });
    });
    </script>

@endsection
