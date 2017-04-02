# Fine Image Gallery


## Install

Fine can be used in two modes.

- **Directory mode.** Copy the `index.php` file into a directory with images.
  When you navigate to the URL of this directory, Fine will display a gallery
  for the images in the directory.

- **Album mode.** Copy the `index.php` file into a directory with a
  subdirectory `albums` which itself contains other directories with images for
  individual albums.

  ~~~
  dir/
    index.php    ← the Fine Image Gallery file
    albums/
      Spain 2016/
        image1.jpg
        image2.jpg
        ...
      Vacation 2017
        image1.jpg
        image2.jpg
        ...
  ~~~

  When you navigate to the URL of `dir`, Fine will start in album mode and
  display an index page from where the galleries for individual albums can be
  accessed.

Fine will automatically detect the mode based on the presence of an `albums`
subdirectory.

There are no other steps involved for a minimal setup. However, it would be
hugely benefical for the script’s performance to create a cache directory in
which resized thumbnail and image files can be stored.

The cache directory has to be named `.fine` (starting with a dot character). In
directory mode, it has to be located in the same directory as the `index.php`
file. In album mode, it has to be located in the `albums` subdirectory.

The cache directory has to be writable by the user account under which the PHP
script runs.


## Requirements

The following PHP versions are supported:

- Should work with all versions >= PHP 5.4.


## Documentation

Supported file formats (checked via file extension):

- GIF (*.gif; no animations, yet)
- JPEG (*.jpeg, *.jpg)
- PNG (*.png)

Keyboard navigation:

- `←`, `→`
  - Album view: Go to next/previous page.
  - Detail view: Go to next/previous image.
- `d`
  - Album view: Open detail view for first image on page.
- `r`
  - Album view, detail view: Open random image from album.
- `ESC`
  - Go up one view level.

Responsive design:

- Most views are fully responsive. For every viewport size, a visually fitting
  arrangement of image thumbnails should be displayed.

Image versions and sizes:

- As of now, Fine does never serve the original upload version of an image.
  Large images will be rescaled proportionally to fit into a rectangle of
  1920x1920 px. All images, even smaller ones, will be resampled to a (probably
  in most cases) lower quality. This is done to reduce cache sizes and to save
  network bandwidth.

HTTP caching:

- Fine tries to avoid delivering unnecessary HTTP responses with image data by
  using HTTP headers like `ETag` or `Last-Modified`.

File system caching:

- If Fine has write access to the `.fine` subdirectory, it will write multiple
  versions with different sizes for every original image to disk. Otherwise,
  the respective versions (e. g. thumbnail) have to be generated on the fly for
  every request/new visitor.

Clean up orphaned cache elements:

- Navigate to `?action=status`. Depending on the number of images, this may
  take a while.
- Fine does not yet automatically delete cached files for images that have been
  deleted or renamed/moved. There’s no real technical disadvantage in keeping
  these old files, but you might want to optimize the cache from time to time
  in order to free disk space on the server.


## Known issues

- The image algorithms should read orientation info from images and rotate them
  if necessary.
- Image meta info (width, height, filesize, …) should be read and cached in
  some way.
- Page titles should better reflect the current view.
- Add link to original or at least zoomable version.


## Credits

- [Marc Ermshaus](http://www.ermshaus.org)


## License

This software is licensed under the MIT License. See source code for full
license info.
