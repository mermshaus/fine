# Changelog


## [Unreleased]

### Added

- Rotate JPEG images according to available EXIF data.
- Rudimentary unit tests.
- Check syntax of generated `index.php` during build process.

### Changed

- Display status view within general layout template.
- Base ETags on modification time of cached files (rather than mtime of
  originals).
- Remove `Application` methods `doRedirect`, `isInSingleAlbumMode`,
  `loadResource`, `statusAction` from public interface.
- Separate hashes for album and image in cache keys.
