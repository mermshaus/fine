# Changelog


## [0.5.0] - 2017-04-17

### Added

- Link to Fine website in footer string.
- Support for touch gestures (swipeleft/swiperight) in album and image views.
- “Scroll to top” link to bottom of certain views.

### Changed

- Use flexbox instead of `display: table;` for detail view.
- Add previous/next controls as image overlay (left and right side of screen).
  Remove from UI controls.
- Remove album name from detail view UI bar.

### Fixed

- Chrome: Images in detail view can no longer cover UI controls.


## [0.4.0] - 2017-04-07

### Added

- Rotate JPEG images according to available EXIF data.
- Rudimentary unit tests.
- Check syntax of generated `index.php` during build process.
- Show album and cache status grouped by album in status view.

### Changed

- Display status view within general layout template.
- Base ETags on modification time of cached files (rather than mtime of
  originals).
- Separate hashes for album and image in cache keys.

### Removed

- Remove `Application` methods `doRedirect`, `isInSingleAlbumMode`,
  `loadResource`, `statusAction` from public interface.


## 0.3.0 - 2017-04-02

First public release.


[0.5.0]: https://github.com/mermshaus/fine/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/mermshaus/fine/compare/v0.3.0...v0.4.0
