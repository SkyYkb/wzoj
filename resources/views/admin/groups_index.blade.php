@extends ('admin.layout')

@section ('title')
{{trans('wzoj.groups')}}
@endsection

@section ('content')

<div class='col-lg-12'>

@can ('create',App\Group::class)
<form method='POST'>
    {{csrf_field()}}
    <button type="submit" class="btn btn-default">+</button>
</form>
@endcan

<table class="table table-striped">
<thead>
    <tr>
        <th>id</th>
	<th>name</th>
    </tr>
</thead>
<tbody>
@foreach ($groups as $group)
	<tr>
	<td>{{$group->id}}</td>
	<td><a href='/admin/groups/{{$group->id}}'>{{$group->name === ''?'(unnamed)':$group->name}}</a></td>
	</tr>
@endforeach
</tbody>
</table>
</div>

@endsection
