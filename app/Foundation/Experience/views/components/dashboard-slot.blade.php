@if(count($widgets) > 0)
    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,18rem),1fr))] gap-4 md:gap-6">
        @foreach($widgets as $widget)
            @include($widget->view, $widget->data)
        @endforeach
    </div>
@endif
