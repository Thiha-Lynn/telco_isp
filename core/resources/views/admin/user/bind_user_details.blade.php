@extends('admin.layout')
<script src="https://telco.mbt.com.mm/assets/admin/plugins/data-table/cdn/jquery.min.js"></script>
@section('content')

    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">{{ __('User Details') }} </h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard',app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.user_query',app()->getLocale()) }}">{{ __('User Query') }}</a></li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <style>
     span {
         float:right;
     }
    </style>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title mt-1">{{ __('Search Results') }}</h3>
                            <a href="{{route('admin.bind_user_query',app()->getLocale())}}" class="btn btn-success" style="float: right;"><i class="fa fa-arrow-left" aria-hidden="true"></i>&nbsp;
                             Back</a>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <div class="row">
                              <div class="col-md-6">
                                  <ul class="list-group">
                                     
                                      <li class="list-group-item">{{__('Register phone')}} <span class="badge">{{App\Models\User::Where('bind_user_id',$names)->first()->phone??''}}</span></li>
                                      <li class="list-group-item">{{__('Register date')}} <span class="badge">{{date('d M Y H:i:s',$bind_user->user_create_time)}}</span></li>
                                      <li class="list-group-item">{{__('Name')}} <span class="badge">{{App\Models\User::Where('bind_user_id',$names)->first()->name??''}}</span></li>
                                      <!--<li class="list-group-item">{{__('Login Password')}} <span class="badge">XXXXXXXX</span></li>-->
                                      <li class="list-group-item">{{__('MBT Account ID')}} <span class="badge">{{$bind_user->user_name}}</span></li>
                                      <li class="list-group-item">{{__('Install address')}} <span class="badge">{{$bind_user->user_address}}</span></li>
                                    <li class="list-group-item">{{__('Sub-company')}} <span class="badge">{{App\SubCompany::find($bind_user->Sub_company)->company_name}}</span></li>

                                    </ul> 
                                                                       </div>
                                  <div class="col-md-6">
                                    
                                    <ul class="list-group">
                                      <li class="list-group-item">{{__('Broadband Name')}} <span class="badge">{{$bind_user->user_real_name??''}}</span></li>
                                      <li class="list-group-item">{{__('Broadband Phone')}} <span class="badge">{{$bind_user->phone??'null'}}</span></li>
                                      <li class="list-group-item">{{__('Now Package')}} <span class="badge">{{$bind_user->Now_package??'null'}}</span></li>
                                      <li class="list-group-item">{{__('Monthly Cost')}} <span class="badge"><?php echo $student; ?></span></li>
                                      <li class="list-group-item">{{__('Expiry Date')}} <span class="badge">{{$bind_user->user_expire_time??'null'}}</span></li>
                                    </ul> 
                                  
                                  </div>
                           </div>
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

