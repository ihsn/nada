/** Classic catalog `image_view` value for thumbnail gallery layout. */
export const IMAGE_GALLERY_VIEW = 'thumbnail';

export function isImageTab(tabType) {
  return tabType === 'image';
}

export function isImageGalleryMode(imageView) {
  return imageView === IMAGE_GALLERY_VIEW;
}

export function imageViewToggleValue(imageView) {
  return isImageGalleryMode(imageView) ? 'gallery' : 'details';
}

export function imageViewFromToggle(value) {
  return value === 'gallery' ? IMAGE_GALLERY_VIEW : '';
}
