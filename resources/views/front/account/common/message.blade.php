@if(Session::has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">

        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <h5><i class="icon fas fa-ban"></i> Alert!</h5>{{Session::get('error')}}
    </div>
@endif
@if(Session::has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="alert" aria-hidden="true"></button>
        <h5><i class="icon fas fa-check"></i> Success!</h5>{{Session::get('success')}}

    </div>
@endif
