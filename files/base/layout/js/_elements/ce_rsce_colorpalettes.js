document.addEventListener("DOMContentLoaded", function (event) {
    var colorPalettes = document.querySelectorAll(".ce_rsce_colorpalettes");
    if (colorPalettes.length) {
        var colorListElements = document.querySelectorAll(".color-list--element");
        colorListElements.forEach(function (element) {
            element.addEventListener("click", function () {
                var storeDesc = this.getAttribute("data-desc");
                var storeTitle = this.getAttribute("data-title");
                var storeImg = this.getAttribute("data-img");

                var parentDiv = this.closest(".colorpalettes-wrapper");

                var storeTopline = this.getAttribute("data-topline");
                var storeHeadline = this.getAttribute("data-headline");
                var storeSubline = this.getAttribute("data-subline");

                var colorpalettesTop = parentDiv.querySelector(".colorpalettes-desc");
                var basicDesc = colorpalettesTop.getAttribute("data-basicdesc");

                var colorPaletteDesc = parentDiv.querySelector(".color-palette--desc");
                if (colorPaletteDesc) {
                    colorPaletteDesc.style.display = "none";
                    setTimeout(function () {
                        if (storeDesc) {
                            colorPaletteDesc.innerHTML = storeDesc;
                        } else {
                            colorPaletteDesc.innerHTML = basicDesc;
                        }
                        colorPaletteDesc.style.display = "block";
                    }, 0);
                }

                var ceTopline = parentDiv.querySelector('.ce--headline span.ce--topline');
                if (ceTopline) {
                    ceTopline.style.display = "none";
                    setTimeout(function () {
                        if (storeTopline) {
                            ceTopline.innerHTML = storeTopline;
                            ceTopline.style.display = "block";
                        }
                    }, 0);
                }

                var ceHeadline = parentDiv.querySelector('.ce--headline  *:not([class*="ce"])');
                if (ceHeadline) {
                    ceHeadline.style.display = "none";
                    setTimeout(function () {
                        if (storeHeadline) {
                            ceHeadline.innerHTML = storeHeadline;
                            ceHeadline.style.display = "block";
                        }
                    }, 0);
                }

                var ceSubline = parentDiv.querySelector('.ce--headline span.ce--subline');
                if (ceSubline) {
                    ceSubline.style.display = "none";
                    setTimeout(function () {
                        if (storeSubline) {
                            ceSubline.innerHTML = storeSubline;
                            ceSubline.style.display = "block";
                        }
                    }, 0);
                }

                var selectedColorElementTitle = parentDiv.querySelector(".selected-color-element--title");
                if (selectedColorElementTitle) {
                    selectedColorElementTitle.style.display = "none";
                    setTimeout(function () {
                        if (storeTitle) {
                            selectedColorElementTitle.innerHTML = storeTitle;
                        }
                        selectedColorElementTitle.style.display = "block";
                    }, 0);
                }

                var imageHolder = parentDiv.querySelector(".image-holder");
                if (imageHolder) {
                    imageHolder.style.opacity = 0;
                    imageHolder.style.display = "none";

                    setTimeout(function () {
                        if (storeImg) {
                            imageHolder.style.backgroundImage = "url(" + storeImg + ")";
                        }
                        imageHolder.style.display = "block";
                        imageHolder.style.opacity = 1;
                    }, 0);
                }
            });
        });
    }
}, {passive: true});