@extends('admin.layout')
<script src="https://telco.mbt.com.mm/assets/admin//plugins/data-table/cdn/jquery.min.js"></script>
<script src="https://telco.mbt.com.mm/assets/admin//plugins/data-table/cdn/tableToExcel.js"></script>
@section('content')

<style>
    #updateusers_filter, #updateusers_info, #updateusers_paginate{
        display: none !important;
    }
</style>

    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">{{ __('User Update') }} </h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard',app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item">{{ __('User Update') }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
     @if(Session::has('message'))
    <div class="alert alert-success alert-dismissible" style="width: fit-content;">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Success!</strong> {{Session::get('message')}}
    </div>
    @endif
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary card-outline" style="border-radius:0px!important;">
                        <div class="card-header">
                            <h1 class="card-title mt-1">{{ __('Search Conditions') }}</h1>
                        </div>
                        <div class="card-body">
                            <form class="form-horizontal" action="{{ route('admin.user_update',app()->getLocale()) }}" method="GET">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Register phone') }}<span class="text-danger">*</span></label>
                                        <div class="col-sm-12">
                                           <input type="number" class="form-control" id="phone" name="userphone" placeholder="{{ __('Enter register phone number') }}">
                                        </div> 
                                    </div>
                                        
                                    <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('User Name') }}<span class="text-danger">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="text" class="form-control" id="accountID" name="username" placeholder="{{ __('username') }}">
                                        </div> 
                                    </div>
                                        
                                    <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Account Status') }}<span class="text-danger">*</span></label>
                                        <div class="col-sm-12">
                                            <select class="form-control lang" name="acc_status">
                                                <option value="0"  >{{ __('Normal') }}</option>
                                                <option value="1"  >{{ __('Disabled') }}</option>
                                            </select>
                                        </div> 
                                    </div>
                                
                                    <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Register date from') }}<span class="text-danger">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="date" name="reg_date_str" class="form-control">
                                        </div> 
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Register date end') }}<span class="text-danger">*</span></label>
                                        <div class="col-sm-12">
                                            <input type="date" name="reg_date_end" class="form-control">
                                        </div> 
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary" style="margin-top: 30px;">{{ __('Search') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>    
        </div>   
        
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title mt-1">{{ __('Search result') }}</h3>

                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                           <table id="updateusers" class="display nowrap  table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Register phone') }}</th>
                                        <!--<th>{{ __('Login Password') }}</th>-->
                                        <th>{{ __('Register date') }}</th>
                                        <!--<th>{{ __('Bind Date') }}</th>-->
                                        <!--<th>{{ __('User Account(ID)') }}</th>-->
                                        <!--<th>{{ __('Sub Company') }}</th>-->
                                        <th>{{ __('Account Status') }}</th>
                                        <th>{{ __('Operating') }}</th>
                                      
                                    </tr>
                                </thead>
                                <tbody id="myTable">
                                    @foreach($bind_user as $index =>$value)
                                    <tr>
                                        <td>{{$index+1}}</td>
                                        <td>{{$value->name}}</td>    
                                        <td>{{$value->phone}}</td>
                                        <!--<td>XXXXXXXX</td>-->
                                        <td>{{date('Y-m-d', strtotime($value->created_at))}}</td>
                                      
                                        <td><?php 
                                         if($value->user_status == 0)
                                         {
                                            $status = "<b class='badge badge-warning'>Normal</b>"; 
                                         }else
                                         {
                                             $status = "<b class='badge badge-danger'>Disabled</b>";
                                         }
                                         echo $status;?></td>
                                        <td><a data-toggle="modal" data-target="#myModal{{$index}}"style="cursor: pointer;color:#fff;margin-top: 4px;" class="badge badge-primary mb-2" >Update</a></td>
                                          <div class="modal" id="myModal{{$index}}">
                                          <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                        
                                              <!-- Modal Header -->
                                              <div class="modal-header">
                                                <h4 class="modal-title">Query From {{App\Models\User::find($value->id)->name}}</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                              </div>
                                        
                                              <!-- Modal body -->
                                              <div class="modal-body">
                                            <form action="{{route('admin.update_users_query',app()->getLocale())}}" method="post" >
                                                @csrf
                                                <input type="hidden" name="query_id" value="{{$value->id}}">
                                                <input type="hidden" name="sub_company" value="{{$value->sub_company}}">
                                               <div class="row">
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Username') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="username"  value="{{App\Models\User::find($value->id)->name}}" class="form-control" >
                                                  </div> 
                                                </div>
                                               
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Phone') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12"> 
                                                      <input type="number" name="phone"  value="{{$value->phone}}" class="form-control" >
                                                  </div> 
                                                </div>
                                              <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Register Date') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12"> 
                                                      <input type="text" name="reg_date"  readonly value="{{date('Y-m-d', strtotime($value->created_at))}}" class="form-control" >
                                                  </div> 
                                                </div>
                                              
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('User Status') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <select class="form-control " name="user_status">  
                                                        <option value="0" <?php echo ($value->user_status == 0)?"selected":"" ?> >{{__('Normal')}}</option>
                                                        <option value="1" <?php echo ($value->user_status == 1)?"selected":"" ?> >{{__('Disabled')}}</option>
                                                     </select>
                                                  </div> 
                                                </div>
                                                </div>
                                              </div>
                                            <!-- Modal footer -->
                                              <div class="modal-footer">
                                                <button type="submit" class="btn btn-success" value="Submit" name="Submit">{{__('Submit')}}</button>
                                              </div>
                                          </form>
                                            </div>
                                          </div>
                                        </div>
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
