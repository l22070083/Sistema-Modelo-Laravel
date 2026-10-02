@foreach(['success', 'error'] as $type)
    @if(session($type))<div role="alert" class="alert alert-{{ $type === 'error' ? 'danger' : 'success' }}">{{ session($type) }}</div>@endif
@endforeach
@if($errors->any())<div role="alert" class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
