@extends('admin.layout')
<script src="https://telco.mbt.com.mm/assets/admin/plugins/data-table/cdn/jquery.min.js"></script>
<!--<script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>-->
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
                    <h1 class="m-0 text-dark">{{ __('User Apply install Record') }} </h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard',app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item">{{ __('Apply Install Records') }}</li>
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

                    <div class="card card-primary card-outline " style="border-radius:0px!important;">
                          <div class="card-header">
                            <h1 class="card-title mt-1">{{ __('Search Conditions') }}</h1>
                            <!--<button type="button" style="color:#fff;float: right;" class="btn btn-xs btn-success collapsed mb-2" ><i class="filter-m-blue"></i>{{ __('Advance Filter') }} &nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-angle-down"></i></button>&nbsp;&nbsp;&nbsp;-->
                           <!--&nbsp;<a href="{{route('admin.install_query',app()->getLocale())}}" style="color:#fff;float: right;margin-right: 7px;" class=" btn-xs btn-danger mb-2" >{{ __('Referesh') }} &nbsp;<i class="fa-refresh"></i></a>-->
                           <!--<a onclick="exportReportToExcel(this)" style="color:#fff;float: right;margin-right: 7px;" class="btn btn-xs btn-success mb-2" >{{ __('Excel export') }}  &nbsp;<i class="fas fa-file-excel"></i></a>-->
                         </div>
                        <div class="card-body">
                             <form id="myform"class="form-horizontal" action="{{ route('admin.search',app()->getLocale()) }}" method="get"> @csrf
                               <div class="row">
                                     <div class="col-md-4">
                                        <label class="col-sm- control-label">{{ __('Register Phone') }}<span class="text-danger">*</span></label>
                                          <div class="col-sm-12">
                                            <input type="text" class="form-control" id="accountID" name="Register_phone" placeholder="{{ __('Register Phone') }}">
                                          </div> 
                                        </div>
                                     
                                     <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Action') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                           <select class="form-control lang shubham" id="myInput_change" name="action">
                                            <option value="" selected disabled  >--{{ __('Select Action') }}--</option>
                                            <option value="1" >{{ __('Accept') }}</option>
                                            <option value="2">{{ __('No Accept') }}</option>
                                            <option value="3"  >{{ __('Processing') }}</option>
                                            <option value="4"  >{{ __('Complete') }}</option>
                                           </select>
                                          </div> 
                                        </div>
                                        
                                   
                                      
                                        <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Report date from') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <input type="date" name="start_date" class="form-control">
                                          </div> 
                                        </div>
                                        
                                        <div class="col-md-3 ">
                                        <label class="col-sm-4 control-label">{{ __('Report date end') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <input type="date" name="end_date" class="form-control">
                                          </div> 
                                        </div>
                                       
                                        <div class="form-group row mr-8" >
                                            <div class="offset-sm-2 col-sm-10" style="margin-top: 31px;">
    
                                                <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                                            </div>
                                        </div>
                                     </div>
                               </form>
                           
                        </div>
                    </div>
                </div>    
        </div>   
          <a href="{{ route('admin.export_install_query_excel',app()->getLocale()) }}" target="_blank">
            <button class="btn btn-success">Excel</button>
        </a>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title mt-1">{{ __('Search Results') }}</h3>

                        </div>
                        <!-- /.card-header -->
                          <div class="card-body">
                          <div id="demo" class="display">
                           <table id="installquery" class="table table-striped table-bordered data_table">
                                <thead>
                                    <tr>
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Username') }}</th>
                                        <!--<th>{{ __('User Account ID') }}</th>-->
                                        <!--<th>{{ __('Sub Company') }}</th>-->
                                        <th>{{ __('Report Date') }}</th>
                                        <th>{{ __('Phone') }}</th>
                                        <th>{{ __('Apply Address') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                 <tbody id="myTable">
                                    @if(!empty($user_query))
                                    @foreach($user_query as $index =>$value)
                                    <tr>
                                        <td>{{$index+1}}</td>
                                        <td>{{App\Models\User::find($value->user_id)->name??''}}</td>    
                                        <!--<td><a class="details" data-toggle="modal" data-target="#myuser_model{{$value->user_id}}" >{{App\Models\User::find($value->user_id)->uniq_id??''}}</a></td>-->
                                        <!--<td>{{App\SubCompany::find($value->sub_company)->company_name??''}}</td>-->
                                        <td>{{$value->apply_date_start}}</td>
                                        <td>{{$value->user_number}}</td>
                                        <td>{{$value->address}}</td>
                                        <td><?php 
                                         
                                          if($value->query_status == 1)
                                         { ?>
                                           <b class='badge badge-success'>{{__('Accept')}}</b>
                                        <?php }elseif($value->query_status == 2)
                                         { ?>
                                         <b class='badge badge-danger'>{{__('No Accept')}}</b>
                                         <?php }elseif($value->query_status == 3)
                                         {?> 
                                           <b class='badge badge-warning'>{{__('Processing')}}</b>
                                        <?php }elseif($value->query_status == 4)
                                         { ?>
                                             
                                             <b class='badge badge-primary'>{{__('Complete')}}</b>
                                         <?php } else {}
                                         ?>
                                         
                                         
                                         <br><a data-toggle="modal" data-target="#myModal{{$index}}"style="cursor: pointer;color:#fff;margin-top: 4px;" class="badge badge-primary mb-2" >{{__('Update')}}</a></td>
                                    </tr>
                                    <div class="modal" id="myModal{{$index}}">
                                          <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                        
                                              <!-- Modal Header -->
                                              <div class="modal-header">
                                                <h4 class="modal-title">{{__('Query From')}}{{App\Models\User::find($value->user_id)->name??''}}</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                              </div>
                                        
                                              <!-- Modal body -->
                                              <div class="modal-body">
                                            <form action="{{route('admin.update_install_query',app()->getLocale())}}" method="post" >
                                                @csrf
                                                <input type="hidden" name="query_id" value="{{$value->apply_id}}">
                                                <input type="hidden" name="install_user_id" value="{{$value->user_id}}">
                                                  <input type="hidden" name="user_id" value="{{App\Models\User::find($value->id)->name??''}}">
                                               <div class="row">
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Username') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="username" readonly value="{{App\Models\User::find($value->user_id)->name??''}}" class="form-control" >
                                                     
                                                     <input type="hidden" name="users_hidden" value="{{App\Models\User::find($value->user_id)->bind_user_id??''}}"/>
                                                  </div> 
                                                </div>
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Number') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12"> 
                                                      <input type="text" name="sub_com_id" readonly value="{{$value->user_number}}" class="form-control" >
                                                  </div> 
                                                </div>
                                                
                                                <!--<div class="col-md-6">-->
                                                <!--<label class="col-sm-4 control-label">{{ __('Sub Company') }}<span class="text-danger">*</span></label>-->
                                                <!-- <div class="col-sm-12"> -->
                                                <!--      <input type="text" name="sub_com_id" readonly value="{{App\SubCompany::find($value->sub_company)->company_name??''}}" class="form-control" >-->
                                                <!--  </div> -->
                                                <!--</div>-->
                                                
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Install Date') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="pay_date" readonly value="{{$value->apply_date_start}}" class="form-control" >
                                                  </div> 
                                                </div>
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Address') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="pay_date" readonly value="{{$value->address}}" class="form-control" >
                                                  </div> 
                                                </div>
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Action') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <select class="form-control " name="status">
                                                        <option value="1" <?php echo ($value->query_status == 1)?"selected":"" ?> >{{__('Accept')}}</option>
                                                        <option value="2" <?php echo ($value->query_status == 2)?"selected":"" ?> >{{__('No Accept')}}</option>
                                                        <option value="3" <?php echo ($value->query_status == 3)?"selected":"" ?> >{{__('Processing')}}</option>
                                                        <option value="4" <?php echo ($value->query_status == 4)?"selected":"" ?> >{{__('Complete')}}</option>
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
                            {{ $user_query->links() }}
                             </div>
                                   
                                    @endif
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
       $(".shubham").on("change", function() {
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
          $("#shubham").on("keyup", function() {
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
$(document).ready(function(){
  $("#myform").on("submit", function(){
    //$("#pageloader").fadeIn();
  });//submit
});//document ready
</script>
<script>
// 	 function exportReportToExcel() {
// 		  let table = document.getElementsByTagName("table"); // you can use document.getElementById('tableId') as well by providing id to the table tag
// 		  TableToExcel.convert(table[0], { // html code may contain multiple tables so here we are refering to 1st table tag
// 			name: `install_query.xlsx`, // fileName you could use any name
// 			sheet: {
// 			  name: 'Sheet 1' // sheetName
// 			}
// 		  });
// 		}
	</script>
	<script>
	$(document).ready(function(){
		$('.toggle').hide();
	  $(".collapsed").click(function(){
		 
		$(".toggle").slideToggle();
	  });
	});
	</script>
	
