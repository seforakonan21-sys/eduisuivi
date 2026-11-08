@if(session('succes'))
    <div class="succes">{{ session('succes') }}</div>
@endif
@if($errors->any())
    <div class="erreur" style="margin-bottom:14px">
        @foreach($errors->all() as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif