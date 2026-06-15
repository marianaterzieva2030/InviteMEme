const canvas = document.getElementById("inviteCanvas");
const ctx = canvas.getContext("2d");

let backgroundImage = null;
let uploadedImage = null;

let textLayers = [];
let activeIndex = null;

let dragOffsetX = 0;
let dragOffsetY = 0;

const templateSelect = document.getElementById("templateSelect");
const imageInput = document.getElementById("imageInput");
const typeInput = document.getElementById("typeOptions");
const titleInput = document.getElementById("titleInput");
const dateInput = document.getElementById("dateInput");
const timeInput = document.getElementById("timeInput");
const roomInput = document.getElementById("roomInput");
const descriptionInput = document.getElementById("descriptionInput");
const colorInput = document.getElementById("colorInput");
const sizeInput = document.getElementById("sizeInput");
const fontInput = document.getElementById("fontInput");
const canvasData = document.getElementById("canvasData");
const inviteForm = document.getElementById("inviteForm");
const shareBtn = document.getElementById("shareBtn");
const textLayersContainer = document.getElementById("textLayersContainer");
const addTextBtn = document.getElementById("addTextBtn");
const infoBox = document.getElementById("inviteInfo");

const defaultCanvasWidth = 600;
const defaultCanvasHeight = 350;
const maxPreviewSize = 600;

colorInput.addEventListener("input", updateAllLayersStyle);
fontInput.addEventListener("change", updateAllLayersStyle);
sizeInput.addEventListener("input", updateAllLayersStyle);

function updateAllLayersStyle() {
    textLayers.forEach(layer => {
        layer.color = colorInput.value;
        layer.font = fontInput.value;
        layer.size = sizeInput.value;
    });

    drawCanvas();
}

function setCanvasAspect(img) {
    if (!img) {
        canvas.width = defaultCanvasWidth;
        canvas.height = defaultCanvasHeight;
        return;
    }

    let width = img.width;
    let height = img.height;

    let ratio;

    if (height > width) {

        ratio = maxPreviewSize / height;

        height = maxPreviewSize;
        width = Math.round(width * ratio);

    } else {

        ratio = maxPreviewSize / width;

        width = maxPreviewSize;
        height = Math.round(height * ratio);
    }

    canvas.width = width;
    canvas.height = height;
}

function drawCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (uploadedImage) {
        ctx.drawImage(uploadedImage, 0, 0, canvas.width, canvas.height);
    } else if (backgroundImage) {
        ctx.drawImage(backgroundImage, 0, 0, canvas.width, canvas.height);
    } else {
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    textLayers.forEach((layer) => {
        ctx.font = sizeInput.value + "px " + fontInput.value;
        ctx.fillStyle = colorInput.value;

        ctx.fillText(layer.text, layer.x, layer.y);

    });
}

function createTextInputLayer(layer) {

    const wrapper = document.createElement("div");
    wrapper.style.margin = "5px";

    const input = document.createElement("input");
    input.type = "text";
    input.value = layer.text;
    input.placeholder = "Текст ";

    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.textContent = "x";

    removeBtn.addEventListener("click", () => {
        const i = textLayers.indexOf(layer);
        if (i !== -1) {
            textLayers.splice(i, 1);
        }
        wrapper.remove();
        drawCanvas();
    });

    input.addEventListener("input", () => {
        layer.text = input.value;
        drawCanvas();
    });


    wrapper.appendChild(input);
    wrapper.appendChild(removeBtn);
    textLayersContainer.appendChild(wrapper);

    drawCanvas();
}

function updateAllLayersStyle() {
    textLayers.forEach(layer => {
        layer.color = colorInput.value;
        layer.font = fontInput.value;
        layer.size = sizeInput.value;
    });

    drawCanvas();
}

function getLayerAt(x, y) {
    for (let i = textLayers.length - 1; i >= 0; i--) {
        const layer = textLayers[i];

        ctx.font = fontInput.value + " " + sizeInput.value + "px";
        const w = ctx.measureText(layer.text).width;
        const h = layer.size;

        if (
            x >= layer.x &&
            x <= layer.x + w &&
            y >= layer.y - h &&
            y <= layer.y
        ) {
            return i;
        }
    }
    return -1;
}

function clientToCanvasCoords(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    const x = (e.clientX - rect.left) * scaleX;
    const y = (e.clientY - rect.top) * scaleY;
    return { x, y };
}

addTextBtn.addEventListener("click", () => {
    const newLayer = {
        text: "",
        x: 100,
        y: 100,
        size: sizeInput.value,
        color: colorInput.value,
        font: fontInput.value
    };
    textLayers.push(newLayer);
    createTextInputLayer(newLayer);
    drawCanvas();
});

templateSelect.addEventListener("change", function () {
    uploadedImage = null;
    imageInput.value = "";
    const selected = this.selectedOptions[0];
    const imagePath = selected.dataset.image || "";
    const type = selected.dataset.type || "";
    if (type) {
        document.querySelector('input[name="type"][value="' + type + '"]').checked = true;
    }

    if (!imagePath) {
        backgroundImage = null;
        drawCanvas();
        return;
    }
    // Normalize stored path: remove any leading '../' so it's web-relative
    let normalizedPath = imagePath.replace(/^\.\.\//, '');

    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = normalizedPath;
    img.onload = () => {
        backgroundImage = img;
        setCanvasAspect(backgroundImage);
        drawCanvas();
    };
    img.onerror = (err) => {
        console.error('Неуспешно зареждане на шаблон:', normalizedPath, err);
        backgroundImage = null;
        drawCanvas();
    };
    drawCanvas();
});

imageInput.addEventListener("change", function () {
    backgroundImage = null;
    templateSelect.selectedIndex = 0;
    const file = this.files[0];
    if (!file) {
        uploadedImage = null;
        drawCanvas();
        return;
    }

    const img = new Image();
    const objectUrl = URL.createObjectURL(file);
    img.src = objectUrl;
    img.onload = () => {
        uploadedImage = img;
        URL.revokeObjectURL(objectUrl);
        setCanvasAspect(uploadedImage);
        drawCanvas();
    };

    document.querySelector('input[name="type"][value="standard"]').checked = true;
});

canvas.addEventListener("pointerdown", (e) => {
    const p = clientToCanvasCoords(e);
    const index = getLayerAt(p.x, p.y);

    if (index !== -1) {
        activeIndex = index;
        dragOffsetX = p.x - textLayers[index].x;
        dragOffsetY = p.y - textLayers[index].y;
        canvas.setPointerCapture(e.pointerId);
        canvas.style.cursor = "grabbing";
    } else {
        activeIndex = null;
    }

    drawCanvas();
});

canvas.addEventListener("pointermove", (e) => {
    const p = clientToCanvasCoords(e);

    if (activeIndex !== null) {

        textLayers[activeIndex].x = p.x - dragOffsetX;
        textLayers[activeIndex].y = p.y - dragOffsetY;
        canvas.style.cursor = "grabbing";
        drawCanvas();
        return;
    }
    if (getLayerAt(p.x, p.y) !== -1) {
        canvas.style.cursor = "grab";
    } else {
        canvas.style.cursor = "default";
    }
});

canvas.addEventListener("pointerup", (e) => {
    activeIndex = null;
    canvas.releasePointerCapture(e.pointerId);
    canvas.style.cursor = "default";
});

canvas.addEventListener("pointerleave", () => {
    canvas.style.cursor = "default";
});

function updateInfoBox() {
    document.getElementById("pTitle").textContent = titleInput.value;
    document.getElementById("pDate").textContent = dateInput.value;
    document.getElementById("pTime").textContent = timeInput.value;
    document.getElementById("pRoom").textContent = roomInput.value;
    document.getElementById("pDesc").textContent = descriptionInput.value;
}

[
    titleInput,
    dateInput,
    timeInput,
    roomInput,
    descriptionInput
].forEach(el => {
    el?.addEventListener("input", updateInfoBox);
});

inviteForm.addEventListener("submit", function () {
    canvasData.value = canvas.toDataURL("image/png");
});

document.getElementById("downloadBtn").addEventListener("click", function () {
    const link = document.createElement("a");
    link.download = "pokana.png";
    link.href = canvas.toDataURL("image/png");
    link.click();
});

shareBtn.addEventListener("click", function () {
    window.open("https://www.facebook.com/groups/1682521323128544", "_blank");
});

updateInfoBox();
drawCanvas();

// If a template is already selected on page load, trigger change to load it
if (templateSelect && templateSelect.value) {
    templateSelect.dispatchEvent(new Event('change'));
}