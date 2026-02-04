@extends('admin.layout')

@section('content')

<div class="content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">{{ __('Error Code') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard',app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
                    <li class="breadcrumb-item">{{ __('Error Code') }}</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>

<div class="modal" id="myModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">{{ __('Add New Error code') }}</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
    <form action="{{route('admin.uploadmessage',app()->getLocale())}}" method="post" enctype="multipart/form-data" >
        @csrf
       
       <div class="row">
        <div class="col-md-6">
        <label class="col-sm-4 control-label">{{ __('Error Key') }}<span class="text-danger">*</span></label>
         <div class="col-sm-12">
             <input type="text" name="key" required placeholder="{{ __('Enter Error Key') }}" class="form-control" >
          </div> 
        </div>
         <div class="col-md-6">
        <label class="col-sm-4 control-label">{{ __('English  Value') }}<span class="text-danger">*</span></label>
         <div class="col-sm-12">
             <input type="text" name="value" required placeholder="{{ __('Enter Error Value') }}"   required class="form-control" >
          </div> 
        </div>
        
         <div class="col-md-6" style="padding-top: 11px;">
        <label class="col-sm-4 control-label">{{ __('Burmese  Value') }}<span class="text-danger">*</span></label>
         <div class="col-sm-12">
             <input type="text" name="Burmese" required placeholder="{{ __('Enter Error Value') }}"   required class="form-control" >
          </div> 
        </div>
        
         <div class="col-md-6" style="padding-top: 11px;">
        <label class="col-sm-4 control-label">{{ __('Chinese  Value') }}<span class="text-danger">*</span></label>
         <div class="col-sm-12">
             <input type="text" name="Chinese" required placeholder="{{ __('Enter Error Value') }}"   required class="form-control" >
          </div> 
        </div>
       </div>
      </div>
    <!-- Modal footer -->
      <div class="modal-footer">
        <button type="submit" class="btn btn-success" value="Submit" name="Submit">{{ __('Submit') }}</button>
      </div>
  </form>
    </div>
  </div>
</div>
 <section class="content">
        <div class="container-fluid">
            <a data-toggle="modal" data-target="#myModal"  style="color:#fff;" class="btn btn-success  waves-effect waves-light collapsed mb-2">{{ __('Add Error code') }}</a>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title mt-1">{{ __('Error Code List') }}</h3>

                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table class="table table-striped table-bordered data_table">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Key') }}</th>
                                        <th>{{ __('English Value') }}</th>
                                        <th>{{ __('Burmese Value') }}</th>
                                        <th>{{ __('Chinese Value') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($banner as $index=>$value)
                                     <tr>
                                        <td>
                                           {{$index+1}}
                                        </td>
                                        <td>
                                             {{$value->key}}
                                        </td>
                                         <td>
                                             {{$value->value}}
                                        </td>
                                        <td>
                                             {{$value->burmese_language??'Null'}}
                                        </td>
                                        <td>
                                             {{$value->chinese_language??'Null'}}
                                        </td>
                                         <td>
                                         <a  href="{{route('admin.deleteerrormessage',[app()->getLocale(),$value->id])}}" title='Delete Banner' class="badge badge-danger"><i class="fas fa-trash"></i> </a>
                                         &nbsp;
                                          <a data-toggle="modal" style="color:white;cursor:pointer;" data-target="#myModal{{$index}}" title='Edit Banner' class="badge badge-primary">{{__('Edit')}} </a>
                                        </td>
                                        
                                        <div class="modal" id="myModal{{$index}}">
                                          <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                        
                                              <!-- Modal Header -->
                                              <div class="modal-header">
                                                <h4 class="modal-title">{{__('Update Error code')}}</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                              </div>
                                        
                                              <!-- Modal body -->
                                              <div class="modal-body">
                                            <form action="{{route('admin.editerrormessage',app()->getLocale())}}" method="post" enctype="multipart/form-data" >
                                                @csrf
                                               <input type="hidden" name="key" value="{{$value->key}}">
                                                <input type="hidden" name="error_id" value="{{$value->id}}">
                                               <div class="row">
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Error Key') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="key" value="{{$value->key}}" required placeholder="{{ __('Enter Error Key') }}" value="" class="form-control" >
                                                  </div> 
                                                </div>
                                                
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('English Value') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="value" value="{{$value->value}}" required placeholder="{{ __('Enter Error Value') }}" value="" class="form-control" >
                                                  </div> 
                                                </div>
                                                
                                                 <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Burmese Value') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="Burmese" value="{{$value->burmese_language}}" required placeholder="{{ __('Enter Error Value') }}" value="" class="form-control" >
                                                  </div> 
                                                </div>
                                                
                                                 <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Chinese Value') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="Chinese" value="{{$value->chinese_language}}" required placeholder="{{ __('Enter Error Value') }}" value="" class="form-control" >
                                                  </div> 
                                                </div>
                                               
                                               </div>
                                              </div>
                                            <!-- Modal footer -->
                                              <div class="modal-footer">
                                                <button type="submit" class="btn btn-success" value="Submit" name="Submit">{{ __('Submit') }}</button>
                                              </div>
                                          </form>
                                            </div>
                                          </div>
                                        </div>
                                        
                                    </tr>
                               @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
    </section>
<!--<section class="content">-->
<!--    <div class="container-fluid">-->
<!--        <div class="row">-->
<!--            <div class="col-md-12">-->
<!--                <div class="card card-primary card-outline">-->
<!--                    <div class="card-header">-->
<!--                        <h3 class="card-title">{{ __('Add Banner for  App') }} </h3>-->
<!--                           <div class="card-tools d-flex">-->
<!--                        </div>-->
<!--                    </div>-->
                    <!-- /.box-header -->
<!--                    <div class="card-body">-->
<!--                        <form class="form-horizontal" action="" method="POST" >-->
<!--                            @csrf-->
                            
<!--                            <div class="form-group row">-->
<!--                                <label for="website_title" class="col-sm-2 control-label">{{ __('Banner Title') }} <span-->
<!--                                        class="text-danger">*</span></label>-->

<!--                                <div class="col-sm-10">-->
<!--                                    <input type="text" class="form-control" name="website_title" value="" placeholder="{{ __('Banner Title') }}">-->
<!--                                    @if ($errors->has('website_title'))-->
<!--                                    <p class="text-danger"> {{ $errors->first('website_title') }} </p>-->
<!--                                    @endif-->
<!--                                </div>-->
<!--                            </div>-->
                           
<!--                            <div class="form-group row">-->
<!--                                <label class="col-sm-2 control-label">{{ __('Banner Image') }} <span class="text-danger">*</span></label>-->
<!--                                <div class="col-sm-10">-->
<!--                                    <img class="mb-3 show-img img-demo" src="" alt="">-->
                                
<!--                                    <div class="custom-file">-->
<!--                                        <label class="custom-file-label" for="header_logo">Choose New Image</label>-->
<!--                                        <input type="file" class="custom-file-input up-img" name="header_logo" id="header_logo">-->
<!--                                    </div>-->
<!--                                    <p class="help-block text-info">{{ __('Upload 150X40 (Pixel) Size image for best quality.-->
<!--                                        Only jpg, jpeg, png image is allowed.') }}-->
<!--                                    </p>-->
<!--                                </div>-->

<!--                            </div>-->
<!--                            <div class="form-group row">-->
<!--                                <div class="offset-sm-2 col-sm-10">-->
<!--                                    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>-->
<!--                                </div>-->
<!--                            </div>-->

<!--                        </form>-->

<!--                    </div>-->
                    <!-- /.box-body -->
<!--                </div>-->

<!--            </div>-->
           
<!--        </div>-->
<!--    </div>-->


<!--</section>-->

@endsection
