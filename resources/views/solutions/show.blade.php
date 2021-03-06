@extends ('layouts.master')

@section ('title')
{{trans('wzoj.solution')}}
@endsection

@if ($solution->problemset_id > 0 && $problemset->type <> 'set')
@include ('layouts.contest_header')
@endif

@section ('content')
{!! Breadcrumbs::render('solution', $solution) !!}
<div class="col-xs-12">

<table class="table table-striped">
<thead>
    <tr>
    	<th style='width:6%'>{{trans('wzoj.id')}}</th>
	<th style='width:9%'>{{trans('wzoj.user')}}</th>
	<th style='width:15%'>{{trans('wzoj.problem')}}</th>
	<th style='width:8%'>{{trans('wzoj.score')}}</th>
	<th style='width:6%'>{{trans('wzoj.time_used')}}</th>
	<th style='width:10%'>{{trans('wzoj.memory_used')}}</th>
	<th style='width:7%'>{{trans('wzoj.language')}}</th>
	<th style='width:7%'>{{trans('wzoj.code_length')}}</th>
	<th style='width:8%'>{{trans('wzoj.judger')}}</th>
	<th style='width:12%'>{{trans('wzoj.submitted_at')}}</th>
    </tr>
</thead>
<tbody>
    <tr>
        <td>{{$solution->id}}</td>
	<td><a href='/users/{{$solution->user->id}}'>{{$solution->user->name}}</a></td>
	@if ($solution->problemset_id > 0)
	    @if ($problemset->public || Gate::allows('view', $problemset))
	        <td><a href='/s/{{$solution->problemset->id}}/{{$solution->problem->id}}'>{{$solution->problem->name}}</a></td>
	    @else
	        <td>{{$solution->problem->name}}</td>
	    @endif
	@else
	    @if (Auth::check() && Auth::user()->has_role('admin'))
	        <td><a href='/admin/problems/{{$solution->problem->id}}'>{{$solution->problem->name}}</a></td>
	    @else
	        <td>{{$solution->problem->name}}</td>
	    @endif
	@endif
	<td>
	@if ($solution->status >= 4)
	  {{$solution->score}}
	@else
	  <div id='solution-{{$solution->id}}' data-testcases="{{json_encode($solution->testcases)}}", data-cnttestcases="{{$solution->cnt_testcases}}">
	  {{trans('wzoj.solution_status_'.$solution->status)}}</div>
	@endif
	</td>

	<td>{{$solution->time_used}}ms</td>
	<td>{{sprintf('%.2f', $solution->memory_used / 1024 / 1024)}}MB</td>
	<td>{{trans('wzoj.programing_language_'.$solution->language)}}</td>
	<td>{{$solution->code_length}}B</td>
	<td>{{$solution->judger?$solution->judger->name:""}}</td>
	<td>{{$solution->created_at}}</td>
    </tr>
</tbody>
</table>
<hr>

@if (isset($solution->sim) && $solution->shouldShowSim())
<span style="color:yellow" class="glyphicon glyphicon-warning-sign"></span>
{{trans('wzoj.sim_warning', ['sid' => $solution->sim->solution2_id, 'rate' => $solution->sim->rate])}}
  @if (Auth::check() && Auth::user()->has_role('admin'))
    <a href="/source-compare?lsid={{$solution->id}}&rsid={{$solution->sim->solution2_id}}">{{trans('wzoj.source_compare')}}</a>
  @endif
@endif

@if ($solution->problem->type <> 3)
  @can ('view_code', $solution)
  <h3>{{trans('wzoj.code')}}</h3>
  <button id='code_button' type="button" class="btn btn-xs btn-default" onclick="showOrHideCode();return false;" >—</button>
  <pre id='code_pre' style="display:block;"><code class="language-{{trans('wzoj.programing_lang_short_'.$solution->language)}}">{{$solution->code}}</code></pre>
  @endcan
@endif

@if ($solution->status == SL_JUDGED)

	@if (isset($solution->ce))
		<h3>{{trans('wzoj.compile_error')}}</h3>
		@can ('view_code', $solution)
		<pre>{{$solution->ce}}</pre>
		@endcan
		<hr>
	@else

		<h3>{{trans('wzoj.testcases')}}</h3>
		<table class="table table-striped">
		<thead>
		    <tr>
			<th>{{trans('wzoj.name')}}</th>
			<th>{{trans('wzoj.score')}}</th>
			<th>{{trans('wzoj.time_used')}}</th>
			<th>{{trans('wzoj.memory_used')}}</th>
			<th>{{trans('wzoj.verdict')}}</th>
			<th>{{trans('wzoj.checklog')}}</th>
 		   </tr>
		</thead>
		<tbody>
		@if ($solution->problem->use_subtasks && is_array($solution->problem->subtasks))
		  @foreach ($solution->problem->subtasks as $subtask)
		    <tr>
		      <td colspan="6">{{trans('wzoj.subtask')}} ({{$subtask->score}} pts)</td>
		    </tr>
		    @foreach ($subtask->testcases as $name)
		      @if (isset($testcases[$name]) && ($testcase = $testcases[$name]))
			@include ('layouts.showsolution')
		      @else
		        <tr>
			  <td>{{$name}}</td>
			  <td colspan="5"> -- no data -- </td>
			</tr>
		      @endif
		    @endforeach
		  @endforeach
		@else
		  @foreach ($testcases as $testcase)
		    @include ('layouts.showsolution')
		  @endforeach
		@endif
		</tbody>
		</table>

	@endif

@endif

</div>
@endsection

@section ('scripts')
<script>
$( document ).ready(function() {
	showOrHideCode();
	@if ($solution->status == 3)
		solutions_update_progress($("{{'#solution-'.$solution->id}}"));
	@endif
	solutions_progress(function(s, solution){
		location.reload();
	});
});
</script>
@endsection
