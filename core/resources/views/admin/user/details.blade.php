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
                            <a href="{{route('admin.user_query',app()->getLocale())}}" class="btn btn-success" style="float: right;"><i class="fa fa-arrow-left" aria-hidden="true"></i>&nbsp;
                            {{ __('Back') }} </a>
                        </div>
                        <!-- /.card-header -->
                        
<div class="card-body">
                            <div class="row">
                              <div class="col-md-6">
                                  <ul class="list-group">  
                                      <li class="list-group-item">{{__('Register phone')}} <span class="badge">{{$bind_user->phone??''}}</span></li>
                                      <li class="list-group-item">{{__('Register date')}} <span class="badge">{{date('Y-m-d', strtotime($bind_user->created_at??''))}}</span></li>
                                      <li class="list-group-item">{{__('Name')}} <span class="badge">{{$bind_user->name}}</span></li>
                                       @if($bind_user->bind_user_id != 0 && isset($mbt_bind_user) && $mbt_bind_user)
                                      <li class="list-group-item">{{__('MBT Account ID')}} <span class="badge">{{$mbt_bind_user->user_name ?? 'N/A'}}</span></li>
                                      <li class="list-group-item">{{__('Install address')}} <span class="badge">{{$mbt_bind_user->user_address}}</span></li>
                                     <li class="list-group-item">{{__('Sub-company')}} 
                                        <span class="badge">
                                            @if(!empty($mbt_bind_user->Sub_company))
                                                {{App\SubCompany::find($mbt_bind_user->Sub_company)->company_name}}
                                            @else
                                                N/A
                                            @endif
                                        </span>
                                    </li>

                                      @else
                                       <li class="list-group-item">{{__('MBT Account ID')}} <span class="badge">null</span></li>
                                      <li class="list-group-item">{{__('Install address')}}<span class="badge">null</span></li>
                                      <li class="list-group-item">{{__('Sub-company')}} <span class="badge">null</span></li>

                                      @endif
                                    </ul> 
                                  </div>
                                  <div class="col-md-6">
                                      @if($bind_user->bind_user_id != 0 && isset($mbt_bind_user) && $mbt_bind_user)
                                   <ul class="list-group">
                                      <li class="list-group-item">{{__('Broadband Name')}} <span class="badge">{{$mbt_bind_user->user_real_name??''}}</span></li>
                                      <li class="list-group-item">{{__('Broadband Phone')}} <span class="badge">{{$mbt_bind_user->phone??'null'}}</span></li>
                                      <li class="list-group-item">{{__('Now Package')}} <span class="badge">{{$mbt_bind_user->Now_package??'null'}}</span></li>
                                      <li class="list-group-item">{{__('Monthly Cost')}} <span class="badge"><?php  echo $student; ?></span></li>
                                      <li class="list-group-item">{{__('Expiry Date')}} <span class="badge">{{$mbt_bind_user->user_expire_time??'null'}}</span></li>
                                    <li class="list-group-item">{{__('Login password')}} <span class="badge">{{$bind_user->new_pass??'null'}}</span></li>

                                    </ul> 
                                    @else
                                    <ul class="list-group">
                                      <li class="list-group-item">{{__('Broadband Name')}} <span class="badge">null</span></li>
                                      <li class="list-group-item">{{__('Broadband Phone')}} <span class="badge">null</span></li>
                                      <li class="list-group-item">{{__('Now Package')}} <span class="badge">null</span></li>
                                      <li class="list-group-item">{{__('Monthly Cost')}} <span class="badge">null</span></li>
                                      <li class="list-group-item">{{__('Expiry Date')}} <span class="badge">null</span></li>
                                    <li class="list-group-item">{{__('Login password')}} <span class="badge">null</span></li>

                                    </ul> 
                                    @endif
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

