# Changelog


## [Unreleased]

### Added

- Link to Fine website in footer string.
- Support for touch gestures (swipeleft/swiperight) in album and image views.


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


[Unreleased]: https://github.com/mermshaus/fine/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/mermshaus/fine/compare/v0.3.0...v0.4.0
