@if(count($widgets) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($widgets as $widget)
            @include($widget->view, $widget->data)
        @endforeach
    </div>
@endif
