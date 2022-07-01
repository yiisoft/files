# Yii Files Change Log

## 1.1.0 under development

- Bug #58: Add missed return value and type for callback of `set_error_handler()` function (vjik)
- New #59: Add `beforeCopy`, `afterCopy` callbacks for `FileHelper::copyFile()` and `FileHelper::copyDirectory()` (Gerych1984)
- Add new public method `FileHelper::lastModifiedFromIterator`
- Raise up php version to 8.0 and allow `RecursiveDirectoryIterator` as argument in `FileHelper::lastModifiedTime`

## 1.0.2 January 11, 2022

- Bug #57: Fix return type for callback of `set_error_handler()` function (devanych)

## 1.0.1 December 18, 2021

- Bug #55: Directory separator was not normalized in patterns (darkdef)

## 1.0.0 February 10, 2021

- Initial release.
