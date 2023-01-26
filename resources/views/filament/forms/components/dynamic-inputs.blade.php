<dl>
    @foreach($getItems() as $name => $value)
        <dt>{{ $name }}</dt>
        <dd>{{ $value['id'] }}</dd>
    @endforeach
</dl>