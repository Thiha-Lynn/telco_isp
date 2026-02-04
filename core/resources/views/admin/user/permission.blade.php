@extends('admin.layout')
<script src="https://telco.mbt.com.mm/assets/admin/plugins/data-table/cdn/jquery.min.js"></script>
@section('content')

    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">{{ __('Add Permission') }} </h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard', app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
                       
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content">
        <div class="container-fluid">
           

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Modal Header</h4>
      </div>
      <div class="modal-body">
        <p>Some text in the modal.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>
 <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('Add Social Links') }} </h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="card-body">
                        <form id="slink" class="form-horizontal" action="{{ route('admin.storeSlinks', app()->getLocale()) }}" method="POST" onsubmit="store(event)">
                            @csrf
                            
                            <div class="form-group row">
                                <label class="col-sm-2 control-label">{{ __('Social Icon') }} <span
                                        class="text-danger">*</span></label>

                                <div class="col-sm-10">
                                    <button class="btn btn-secondary biconpicker" data-iconset="fontawesome5" data-icon="fab fa-facebook-f" role="iconpicker"></button>
                                    <input id="inputIcon" type="hidden" name="icon" value="">
                                    @if ($errors->has('icon'))
                                    <p class="text-danger"> {{ $errors->first('icon') }} </p>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="website_title" class="col-sm-2 control-label">{{ __('Social URL') }}<span
                                        class="text-danger">*</span></label>

                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="url" value="" placeholder="{{ __('Social URL') }}">
                                    @if ($errors->has('url'))
                                    <p class="text-danger"> {{ $errors->first('url') }} </p>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="offset-sm-2 col-sm-10">
                                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                </div>
                            </div>

                        </form>

                    </div>
                    <!-- /.box-body -->
                </div>

            </div>
            <!-- /.col -->
        </div>
            <div class="row">
                <button type="button" style="color:#fff;" data-toggle="modal" data-target="#myModal" class="btn btn-success  waves-effect waves-light collapsed mb-2" >Add Permission</button>
                <div class="col-lg-12">
        
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title mt-1">{{ __('Permission List') }}</h3>

                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table class="table table-striped table-bordered data_table">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Operating') }}</th>
                                      
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Shubham Kumar</td>    
                                        <td><a href="#">Delete</a>&nbsp;&nbsp;<a href="#">Edit</a></td>
                                    </tr>
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
@endsection
	<script>
	$(document).ready(function(){
		$('.toggle').hide();
	  $(".collapsed").click(function(){
		 
		$(".toggle").slideToggle();
	  });
	});
	</script>

