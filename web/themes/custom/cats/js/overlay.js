document.addEventListener('DOMContentLoaded', function () {
  var tableCats = document.getElementById('cats-table');
  var images = document.querySelectorAll('.responsive-image');
  var imgContainers = document.querySelectorAll('.image-container');

  images.forEach(function (image, index) {
    var imgContainer = imgContainers[index];

    image.addEventListener('click', function () {
      var body = document.querySelector('body');
      body.classList.add('lock');
      var clonedContainer = imgContainer.cloneNode(true);
      tableCats.appendChild(clonedContainer);
      clonedContainer.classList.add('overlay');
      var clonedImage = clonedContainer.querySelector('.responsive-image');
      clonedImage.classList.add('overlay');
      clonedImage.addEventListener('click', function () {
        tableCats.removeChild(clonedContainer);
        body.classList.remove('lock');
      });
    });
  });
})
