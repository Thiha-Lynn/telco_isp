@extends('admin.layout')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
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
                    <h1 class="m-0 text-dark">{{ __('User Fault Report') }} </h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard',app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item">{{ __('Fault Payment Query') }}</li>
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
                           <form class="toggless" action="{{ route('admin.search_fault_query',app()->getLocale()) }}" method="get"> @csrf
                             <div class="row">
                                     <div class="col-md-4">
                                        <label class="col-sm- control-label">{{ __('MBT Account ID') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                             <input type="text" class="form-control" id="shubham" name="mbt_account_id" placeholder="{{ __('Enter account ID...') }}">
                                          </div> 
                                        </div>
                                        <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Sub Company') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <select class="form-control lang shubham" id="myInput_change" name="sub_company" >
                                               <option value="">--{{__('Select Sub Company')}}--</option>
                                               @foreach($sub_com as $value)
                                                <option value="{{$value->sub_com_id}}">{{$value->company_name}}</option>
                                              @endforeach
                                             </select>
                                          </div> 
                                        </div>
                                        <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Action') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <select class="form-control lang shubham" id="myInput_change" name="action" >
                                               <option value="">--{{__('Select Action')}}--</option>
                                                <option value="3">{{__('Not Accept')}}</option>
                                                <option value="4" >{{__('Accept')}}</option>
                                                <option value="1"  >{{__('Processing')}}</option>
                                                <option value="2"  >{{__('Complete')}}</option>
                                             </select>
                                          </div> 
                                        </div>
                                   </div>
                                  
                                     <div class="row">
                                       <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Report date from') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <input type="date" name="report_start_date" class="form-control">
                                          </div> 
                                        </div>
                                        <div class="col-md-4">
                                        <label class="col-sm-4 control-label">{{ __('Report date end') }}<span class="text-danger">*</span></label>
                                         <div class="col-sm-12">
                                            <input type="date" name="report_end_date" class="form-control">
                                          </div> 
                                        </div>
                                         <div class="col-md-4">
                                       <div class="col-sm-12" style="margin-top: 31px;">
                                             <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                                          </div> 
                                        </div>
                                      </div>  
                                   </form>
                    </div>
                </div>    
        </div>   
        </div>
         <a href="{{ route('admin.export_fault_query_excel',app()->getLocale()) }}">
            <button class="btn btn-success">Excel</button>
        </a>
        <!--<a href="{{ route('admin.export_fault_pdf',app()->getLocale()) }}">-->
        <!--    <button class="btn btn-success">PDF</button>-->
        <!--</a>-->
        </div>
        
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title mt-1">{{ __('Search result') }}</h3>

                        </div>
                        <!-- /.card-header -->
                       <div class="card-body">
                          <div id="demo" class="display">
                           <table id="example" class="display nowrap  table-striped table-bordered">
                                <thead>
                                    <tr>
                                        
                                        <th>{{ __('#') }}</th>
                                        <th>{{ __('Fullname') }}</th>
                                        <th>{{ __('MBT Account ID') }}</th>
                                        <th>{{ __('Sub Company') }}</th>
                                        <th>{{ __('Report Date') }}</th>
                                        <th>{{ __('Phone') }}</th>
                                        <th>{{ __('Address') }}</th>
                                        <th>{{ __('Fault details') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="myTable">
                                    
                                     <?php foreach($fault as $index=>$value) { ?>
                                   
                                    <tr>
                                        <td>{{$index+1}}</td>
                                        <td>{{App\User::find($value->user_id)->name??''}}</td>                                          
                                        @php
                                            $bind_user = App\User::find($value->user_id)->bind_user_id??'';
                                        @endphp
                                        <!--<td><a class="details" data-toggle="modal" data-target="#myuser_model{{$bind_user}}" >{{App\MbtBindUser::find($bind_user)->user_name??''}}</a></td>-->
                                        <td><a class="details" href="{{route('admin.payment_user_detail',[app()->getLocale(),$bind_user])}}">{{App\MbtBindUser::find($bind_user)->user_name??''}}</a></td>    
                                        <td>{{App\SubCompany::find($value->sub_com_id)->company_name ?? 'N/A'}}</td>
                                        <td>{{$value->report_date}}</td>
                                        <td>{{$value->fault_number}}</td>
                                        <td>{{$value->fault_address}}</td>
                                        <td>{{substr($value->fault_details,0,50)}}...</td>
                                        <td><?php 
                                       
                                         if($value->fault_status == 0)
                                         { ?>
                                           <b class='badge badge-success'>{{__('Accept')}}</b>
                                        <?php 
                                             
                                         }elseif($value->fault_status == 1)
                                         { ?>
                                           <b class='badge badge-warning'>{{__('Processing')}}</b>
                                        <?php }elseif($value->fault_status == 3)
                                         { ?>
                                             <b class='badge badge-danger'>{{__('Not Accept')}}</b>
                                         <?php }else
                                         {?> 
                                             <b class='badge badge-primary'>{{__('Complete')}}</b>
                                         <?php } 
                                         ?><br><a data-toggle="modal" data-target="#myModal{{$index}}"style="cursor: pointer;color:#fff;margin-top: 4px;" class="badge badge-primary mb-2" >{{__('Update')}}</a></td>
                                     
                                     <div class="modal" id="myModal{{$index}}">
                                          <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                        
                                              <!-- Modal Header -->
                                              <div class="modal-header">
                                                <h4 class="modal-title">{{__('Query From')}} {{App\User::find($value->user_id)->name??''}}</h4>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                              </div>
                                        
                                              <!-- Modal body -->
                                              <div class="modal-body">
                                            <form action="{{route('admin.update_fault_query',app()->getLocale())}}" method="post" >
                                                @csrf
                                                <input type="hidden" name="query_id" value="{{$value->id}}">
                                                <input type="hidden" name="apllyer_user_id" value="{{$value->user_id}}">
                                               <div class="row">
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Username') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="username" readonly value="{{App\User::find($value->user_id)->name??''}}" class="form-control" >
                                                      <input type="hidden" name="users_fault" value="{{App\User::find($value->user_id)->bind_user_id??''}}"/>

                                                    
                                                  </div> 
                                                </div>
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Phone') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="pay_date" readonly value="{{$value->fault_number}}" class="form-control" >
                                                  </div> 
                                                </div>
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Sub Company') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12"> 
                                                      <input type="text" name="sub_com_id" readonly value="{{App\SubCompany::find($value->sub_com_id)->company_name ?? 'N/A'}}" class="form-control" >
                                                  </div> 
                                                </div>
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Address') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="pay_date" readonly value="{{$value->fault_address}}" class="form-control" >
                                                  </div> 
                                                </div>
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Report Date') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <input type="text" name="pay_date" readonly value="{{$value->report_date}}" class="form-control" >
                                                  </div> 
                                                </div>
                                                <div class="col-md-6">
                                                <label class="col-sm-4 control-label">{{ __('Report Status') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <select class="form-control " name="status">
                                                        <option value="0" <?php echo ($value->fault_status == 0)?"selected":"" ?> >{{__('Accept')}}</option>
                                                         <option value="3" <?php echo ($value->fault_status == 3)?"selected":"" ?> >{{__('Not Accept')}} </option>
                                                        <option value="1" <?php echo ($value->fault_status == 1)?"selected":"" ?> >{{__('Processing')}}</option>
                                                        <option value="2" <?php echo ($value->fault_status == 2)?"selected":"" ?> >{{__('Complete')}}</option>
                                                     </select>
                                                  </div> 
                                                </div>
                                                <div class="col-md-12">
                                                <label class="col-sm-4 control-label">{{ __('Fault detail') }}<span class="text-danger">*</span></label>
                                                 <div class="col-sm-12">
                                                     <textarea class="form-control" name="description" readonly>{{$value->fault_details}}</textarea>
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
                                     <?php } ?>
                              </tbody>
                            </table>
                            {{ $fault->links() }}
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
       $(".shubham").on("change", function() {
        var value = $(this).val().toLowerCase();
        //alert(value);
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
          
          });
        </script>
        

