
<div class='d-none d-md-none d-lg-block mt-4'>
    <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 shadow p-3 pb-3 bg-body rounded bg-primary'>
        
            @if($course_code)
                @if($is_editor)
                    <div class='row'>
                        <div class='@if($toolName) col-9 @else col-10 @endif'>
                            @if($toolName)
                                <div class='row'>
                                    <span class='control-label-notes'>
                                        <span class="fas fa-tools text-warning pe-2" aria-hidden="true"></span>{{$toolName}}
                                    </span>
                                </div>
                                <div class='row'>
                                    <span class='control-label-notes'>
                                        <span class='fas fa-university text-warning pe-2'></span>{{$currentCourseName}}
                                    </span>
                                </div>
                                <div class='row'>
                                    <span class='control-label-notes'>
                                        <span class='fas fa-user text-warning pe-1'></span><span class='text-secondary fs-6 ms-1'>{{course_id_to_prof($course_id)}}</span>
                                        <span class="fas fa-code text-warning pe-1"></span><span class='text-secondary fs-6'>{{$course_code}}</span>                                     
                                    </span>
                                </div>
                            @else
                                <div class='row'>
                                    <span class='control-label-notes'>
                                        <span class='fas fa-university text-warning pe-2'></span>{{$currentCourseName}}
                                    </span>
                                </div>
                                <div class='row'>      
                                    <span class='control-label-notes'>
                                        <span class='fas fa-user text-warning pe-2'></span><span class='text-secondary fs-6'>{{course_id_to_prof($course_id)}}</span> 
                                        <span class="fas fa-code text-warning pe-2"></span><span class='text-secondary fs-6'>{{$course_code}}</span>                              
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class='@if($toolName) col-3 @else col-2 @endif'>
                            @include('layouts.partials.manageCourse',[$urlAppend => $urlAppend,'coursePrivateCode' => $course_code])
                        </div>
                    </div>
                @else
                    <div class='row'>
                        <div class='col-12'>
                            @if($toolName)
                                <div class='row'>
                                    <span class='control-label-notes'>
                                        <span class="fas fa-tools text-warning pe-2" aria-hidden="true"></span>{{$toolName}}
                                    </span>
                                </div>
                                <div class='row'>
                                    <span class='control-label-notes'>
                                        <span class='fas fa-university text-warning pe-2'></span>{{$currentCourseName}}
                                    </span>
                                </div>
                                <div class='row'>
                                    <span class='control-label-notes'>
                                        <span class='fas fa-user text-warning pe-1'></span><span class='text-secondary fs-6 ms-1'>{{course_id_to_prof($course_id)}}</span>
                                        <span class="fas fa-code text-warning pe-1"></span><span class='text-secondary fs-6'>{{$course_code}}</span>                                     
                                    </span>
                                </div>
                            @else
                                <div class='row'>
                                    <span class='control-label-notes'>
                                        <span class='fas fa-university text-warning pe-2'></span>{{$currentCourseName}}
                                    </span>
                                </div>
                                <div class='row'>      
                                    <span class='control-label-notes'>
                                        <span class='fas fa-user text-warning pe-2'></span><span class='text-secondary fs-6'>{{course_id_to_prof($course_id)}}</span> 
                                        <span class="fas fa-code text-warning pe-2"></span><span class='text-secondary fs-6'>{{$course_code}}</span>                              
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div class='d-flex justify-content-center ps-1 pt-1 pb-2'>
                    <span class="control-label-notes">
                        <i class="fas fa-tools text-warning" aria-hidden="true"></i> 
                        {{$toolName}} 
                    </span>
                </div>
            @endif
        
    </div></br>
</div>

<div class='d-block d-md-block d-lg-none mt-4'>
    <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 shadow p-3 pb-3 bg-body rounded bg-primary'>
        
            @if($course_code)
                @if($is_editor)
                    <div class='row'>
                        <div class='col-12'>
                           
                                <table class='table'>
                                    <thead>
                                        @if($toolName)
                                            <tr class='border-0'>
                                                <th class='border-0'>
                                                    <div class='row'>
                                                        <div class='col-2'>
                                                            <span class='control-label-notes'>
                                                                <span class="fas fa-tools text-warning pe-2" aria-hidden="true"></span>
                                                            </span>
                                                        </div>
                                                        <div class='col-10'>
                                                            <span class='control-label-notes fs-6'>
                                                                {{$toolName}}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </th>
                                            </tr>
                                        @endif
                                       
                                        <tr class='border-0'>
                                            <th class='border-0'>
                                                <div class='row'>
                                                    <div class='col-2'>
                                                        <span class='control-label-notes'>
                                                            <span class="fas fa-university text-warning pe-2" aria-hidden="true"></span>
                                                        </span>
                                                    </div>
                                                    <div class='col-10'>
                                                        <span class='control-label-notes fs-6'>
                                                            {{$currentCourseName}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </th>
                                        </tr>
                                        
                                            
                                        <tr class='border-0'>
                                            <th class='border-0'>
                                                <div class='row'>
                                                    <div class='col-2'>
                                                        <span class='control-label-notes'>
                                                            <span class="fas fa-user text-warning pe-2" aria-hidden="true"></span>
                                                        </span>
                                                    </div>
                                                    <div class='col-10'>
                                                        <span class='control-label-notes fs-6'>
                                                            {{course_id_to_prof($course_id)}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </th>
                                        </tr>

                                        <tr class='border-0'>
                                            <th class='border-0'>
                                                <div class='row'>
                                                    <div class='col-2'>
                                                        <span class='control-label-notes'>
                                                            <span class="fas fa-code text-warning pe-2" aria-hidden="true"></span>
                                                        </span>
                                                    </div>
                                                    <div class='col-10'>
                                                        <span class='control-label-notes fs-6'>
                                                           {{$course_code}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </th>
                                        </tr>
                                        
                                        
                                        <tbody>
                                        </tbody>
                                    </thead>
                                </table>
                            
                        </div>
                        <div class='col-12'>
                            @include('layouts.partials.manageCourse',[$urlAppend => $urlAppend,'coursePrivateCode' => $course_code])
                        </div>
                    </div>
                @else
                    <div class='row'>
                        <div class='col-12'>
                            
                                <table class='table'>
                                    <thead>
                                        @if($toolName)
                                            <tr class='border-0'>
                                                <th class='border-0'>
                                                    <div class='row'>
                                                        <div class='col-2'>
                                                            <span class='control-label-notes'>
                                                                <span class="fas fa-tools text-warning pe-2" aria-hidden="true"></span>
                                                            </span>
                                                        </div>
                                                        <div class='col-10'>
                                                            <span class='control-label-notes fs-6'>
                                                                {{$toolName}}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </th>
                                            </tr>
                                        @endif
                                       
                                        <tr class='border-0'>
                                            <th class='border-0'>
                                                <div class='row'>
                                                    <div class='col-2'>
                                                        <span class='control-label-notes'>
                                                            <span class="fas fa-university text-warning pe-2" aria-hidden="true"></span>
                                                        </span>
                                                    </div>
                                                    <div class='col-10'>
                                                        <span class='control-label-notes fs-6'>
                                                            {{$currentCourseName}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </th>
                                        </tr>
                                        
                                            
                                        <tr class='border-0'>
                                            <th class='border-0'>
                                                <div class='row'>
                                                    <div class='col-2'>
                                                        <span class='control-label-notes'>
                                                            <span class="fas fa-user text-warning pe-2" aria-hidden="true"></span>
                                                        </span>
                                                    </div>
                                                    <div class='col-10'>
                                                        <span class='control-label-notes fs-6'>
                                                            {{course_id_to_prof($course_id)}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </th>
                                        </tr>

                                        <tr class='border-0'>
                                            <th class='border-0'>
                                                <div class='row'>
                                                    <div class='col-2'>
                                                        <span class='control-label-notes'>
                                                            <span class="fas fa-code text-warning pe-2" aria-hidden="true"></span>
                                                        </span>
                                                    </div>
                                                    <div class='col-10'>
                                                        <span class='control-label-notes fs-6'>
                                                           {{$course_code}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </th>
                                        </tr>
                                        
                                        
                                        <tbody>
                                        </tbody>
                                    </thead>
                                </table>
                            
                        </div>
                    </div>
                @endif
            @else
                <div class='d-flex justify-content-center ps-1 pt-1 pb-2'>
                    <span class="control-label-notes">
                        <i class="fas fa-tools text-warning text-center" aria-hidden="true"></i> 
                        {{$toolName}} 
                    </span>
                </div>
            @endif
        
    </div></br>
</div>
