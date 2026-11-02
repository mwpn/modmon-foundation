# Experience icons

[Lucide](https://lucide.dev) **1.48.0** (`lucide-static`), ISC — see `LICENSE`.
Each `{name}.svg` holds only the inner markup of the 24 × 24 icon; the
`<svg>` wrapper (stroke, size, `currentColor`) comes from
`<x-foundation::icon>`. Names are the lucide.dev names.

Updating: take `icons/*.svg` from a newer `lucide-static` release, strip
the `<svg>` wrapper and whitespace, allow only `path`, `circle`, `rect`,
`line`, `polyline`, `polygon`, `ellipse` (the markup is printed raw), and
keep `LICENSE`. Renamed or removed icons render nothing — check
`grep -r 'icon name=' Modules app` before updating.
