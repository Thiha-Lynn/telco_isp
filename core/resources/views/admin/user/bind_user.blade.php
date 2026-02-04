@extends('admin.layout')
<script src="https://telco.mbt.com.mm/assets/admin/plugins/data-table/cdn/jquery.min.js"></script>

@section('content')

 <style>
           #user_query_filter, #user_query_info, #user_query_paginate,.buttons-excel{
            display: none !important;
         }
        
    </style>

    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">{{ __('Bind User') }} </h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard',app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item">{{ __('User Query') }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content">
        <div class="container-fluid">
             
         <div class="row">
            
        <div class="col-md-12">

                    <div class="card card-primary card-outline" style="border-radius:0px!important;">
                        <div class="card-body">
                             <form class="form-horizontal" action="{{ route('admin.search_bind_user',app()->getLocale()) }}" method="get"> @csrf

                               <div class="row">

                                       <div class="col-md-3">
                                        <label class="col-sm-4 control-label">{{ __('Register phone') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                           <input type="text" class="form-control" id="phone" name="register_phone" placeholder="{{ __('Enter register phone number') }}">
                                         
                                          </div> 
                                        </div>
                                        <div class="col-md-3">
                                        <label class="col-sm-4 control-label">{{ __('Sub Company') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                             <select class="form-control lang shubham" id="myInput_change" name="sub_com">
                                               <option value="">--{{ __('Sub Company') }}--</option>
                                               @foreach($sub_com as $value)
                                                <option value="{{$value->sub_com_id}}">{{$value->company_name}}</option>
                                              @endforeach
                                             </select>
                                          </div> 
                                        </div>
                                        <div class="col-md-3">
                                         <label class="col-sm-4 control-label">{{ __('MBT Account ID') }}<span class="text-danger">*</span></label>
                                          <div class="col-sm-12">
                                            <input type="text" class="form-control" id="accountID" name="user_account" placeholder="{{ __('Enter account ID') }}">
                                          </div> 
                                          
                                        </div>
                                        <div class="col-md-3">
                                        <label class="col-sm-4 control-label">{{ __('User Status') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                           <select class="form-control lang" name="account_status">
                                               <option value="0"  >{{ __('Normal') }}</option>
                                                <option value="1"  >{{ __('Disabled') }}</option>
                                             </select>
                                          </div> 
                                        </div>
                                        </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                        <label class="col-sm-4 control-label">{{ __('Register date from') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <input type="date" name="reg_date_str" class="form-control">
                                          </div> 
                                        </div>
                                        <div class="col-md-3">
                                        <label class="col-sm-4 control-label">{{ __('Register date end') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <input type="date" name="reg_date_end" class="form-control">
                                          </div> 
                                        </div>
                                        
                                        
                                        <div class="col-md-3">
                                        <label class="col-sm-4 control-label">{{ __('Bind Date from') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <input type="date" name="bind_date_str" class="form-control">
                                          </div> 
                                        </div>
                                        <div class="col-md-3">
                                        <label class="col-sm-4 control-label">{{ __('Bind Date end') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <input type="date" name="bind_date_end" class="form-control">
                                          </div> 
                                        </div>
                                       
                                    </div>
                                    <div class="form-group row mr-8" >
                                        <div class="offset-sm-2 col-sm-10" style="margin-left: 0.667%;margin-top: 11px;">
                                            <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                                        </div>
                                    </div>
                                </form>
                           </div>
                        </div>
                    </div>
                </div> 
                <a href="{{ route('admin.export_binduser_query_excel',app()->getLocale()) }}">
                             <button class="btn btn-success">Excel</button>
                           </a>
        </div>   
        
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title mt-1">{{ __('Search result') }}</h3>

                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                           <table id="bind_user_query" class="display nowrap  table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('MBT Account ID') }}</th>
                                        <th>{{ __('Real Name') }}</th>
                                        <th>{{ __('Register phone') }}</th>
                                        <th>{{ __('Register date') }}</th>
                                        <th>{{ __('Bind Date') }}</th>
                                        <th>{{ __('Unbind Date') }}</th>
                                        <th>{{ __('Sub Company') }}</th>
                                        <th>{{ __('User Status') }}</th>
                                        <th>{{ __('Operating') }}</th>
                                      
                                    </tr>
                                </thead>
                                <tbody id="myTable">
                                    @foreach($bind_user as $k=>$value)
                                        <tr>
                                            <td>{{ ++$k }}</td>
                                            <?php 
                                                $mbt_bind = DB::table('mbt_bind_user')->where('user_name',$value->mbt_id)->first(); 
                                            ?>
                                            
                                            <td>
                                                @if(!empty($mbt_bind->er_id))
                                                    <a href="{{route('admin.binduserdetails',[app()->getLocale(),$mbt_bind->er_id])}}">{{$value->mbt_id}}</a>
                                                @else
                                                    <a href="#">{{$value->mbt_id}}</a>
                                                @endif
                                            </td> 
                                            
                                            <td>{{ $mbt_bind->user_real_name??'Null' }}</td>
                                           
                                            <td>{{App\Models\User::Where('id',$value->user_id)->first()->phone??''}}</td> 
                                          
                                            <td>{{App\Models\User::Where('id',$value->user_id)->first()->created_at??''}}</td> 
    
                                        
                                            <td>{{$value->bind_date??'Null'}}</td> 
                                            <td>{{$value->unbind_date??'Null'}}</td> 
                                            <td>
                                                @if(!empty($mbt_bind->Sub_company))
                                                    {{App\SubCompany::find($mbt_bind->Sub_company)->company_name??'Null'}}
                                                @else
                                                    Null
                                                @endif
                                            </td>
                                            <td>
                                                <?php
                                                    $user = DB::table('users')->where('id',$value->user_id)->first();
                                                    if(!empty($user) && $user->user_status == 0)
                                                    {
                                                        $status = "<b class='badge badge-warning'>Normal</b>";
                                                    }else
                                                    {
                                                        $status = "<b class='badge badge-danger'>Disabled</b>";
                                                    }
                                                    echo $status;
                                                ?>
                                            </td>
                                    
                                            <td>
                                                @if(!empty($mbt_bind->er_id))
                                                    <a href="{{route('admin.binduserdetails',[app()->getLocale(),$mbt_bind->er_id])}}" >{{__('Details')}}</a>
                                                @else
                                                    <a href="#">{{__('Details')}}</a>
                                                @endif
                                            </td>                                
                                        </tr>
                                    @endforeach
                              </tbody>
                            </table>
                            {{ $bind_user->links() }}
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
	<script>
    $(document).ready(function(){
       $(".lang").on("change", function() {
        var value = $(this).val().toLowerCase();
       // alert(value);
        $("#myTable tr").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
      });
     
    });
    </script>
     <script>
          $(document).ready(function(){
          $("#phone").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#myTable tr").filter(function() {
              $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
          });
          $("#accountID").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#myTable tr").filter(function() {
              $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
          });
          });
        </script>
     <script>
	 function exportReportToExcel() {
		  let table = document.getElementsByTagName("table"); // you can use document.getElementById('tableId') as well by providing id to the table tag
		  TableToExcel.convert(table[0], { // html code may contain multiple tables so here we are refering to 1st table tag
			name: `user_query.xlsx`, // fileName you could use any name
			sheet: {
			  name: 'Sheet 1' // sheetName
			}
		  });
		}
	</script>
