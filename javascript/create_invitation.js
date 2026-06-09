const canvas = document.getElementById("inviteCanvas");
const ctx = canvas.getContext("2d");

let backgroundImage = null;
let uploadedImage = null;
let title = "Тема на поканата";
let date = "";
let time = "";
let room = "";
let presenter = "";
let descriptionText = "";
let textX = 30;
let textY = 30;
let isDragging = false;
let descriptionX = 30;
let descriptionY = 250;
let isDraggingDescription = false;
const dragOffset = { x: 0, y: 0 };

const templateSelect = document.getElementById("templateSelect");
const imageInput = document.getElementById("imageInput");
const titleInput = document.getElementById("titleInput");
const dateInput = document.getElementById("dateInput");
const timeInput = document.getElementById("timeInput");
const roomInput = document.getElementById("roomInput");
const presenterInput = document.getElementById("presenterInput");
const descriptionInput = document.getElementById("descriptionInput");
const colorInput = document.getElementById("colorInput");
const sizeInput = document.getElementById("sizeInput");
const fontInput = document.getElementById("fontInput");
const canvasData = document.getElementById("canvasData");
const inviteForm = document.getElementById("inviteForm");
const shareBtn = document.getElementById("shareBtn");

const lineSpacing = 5;
const defaultCanvasWidth = 600;
const defaultCanvasHeight = 350;
const maxPreviewWidth = 760;
const maxPreviewHeight = 520;

function setPreviewSize(width, height) {
    const preview = document.getElementById("previewArea");
    if (!preview) return;
    preview.style.width = Math.round(width) + "px";
    preview.style.height = Math.round(height) + "px";
}

function setCanvasAspect(img) {
    if (!img) {
        canvas.width = defaultCanvasWidth;
        canvas.height = defaultCanvasHeight;
        setPreviewSize(defaultCanvasWidth, defaultCanvasHeight);
        return;
    }

    const ratio = img.width / img.height;
    let width = img.width;
    let height = img.height;

    if (width > maxPreviewWidth) {
        width = maxPreviewWidth;
        height = Math.round(width / ratio);
    }

    if (height > maxPreviewHeight) {
        height = maxPreviewHeight;
        width = Math.round(height * ratio);
    }

    canvas.width = Math.round(width);
    canvas.height = Math.round(height);
    setPreviewSize(canvas.width, canvas.height);
}

function getTextLines() {
    const lines = [];
    if (title) {
        lines.push("Тема: " + title);
    }
    if (date || time) {
        lines.push("Дата: " + date + " " + time);
    }
    if (room) {
        lines.push("Зала: " + room);
    }
    if (presenter) {
        lines.push("Презентиращ: " + presenter);
    }
    if (!lines.length) {
        lines.push("Текст на поканата");
    }
    return lines;
}

function getTextBlockMetrics() {
    ctx.font = sizeInput.value + "px " + fontInput.value;
    const lines = getTextLines();
    let maxWidth = 0;
    lines.forEach(line => {
        maxWidth = Math.max(maxWidth, ctx.measureText(line).width);
    });
    const height = lines.length * (parseInt(sizeInput.value, 5) + lineSpacing) - lineSpacing;
    return {
        x: textX,
        y: textY,
        width: maxWidth + 20,
        height: height + 20,
    };
}

function drawCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (uploadedImage) {
        ctx.drawImage(uploadedImage, 0, 0, canvas.width, canvas.height);
    } else if (backgroundImage) {
        ctx.drawImage(backgroundImage, 0, 0, canvas.width, canvas.height);
    } else {
        ctx.fillStyle = "#ffffff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    const lines = getTextLines();
    ctx.fillStyle = colorInput.value;
    ctx.font = sizeInput.value + "px " + fontInput.value;
    ctx.textBaseline = "top";
    let currentY = textY;

    lines.forEach(line => {
        ctx.fillText(line, textX, currentY);
        currentY += parseInt(sizeInput.value, 10) + lineSpacing;
    });
    if (descriptionText) {
        ctx.fillText(descriptionText, descriptionX, descriptionY);
    }
    const metrics = getTextBlockMetrics();
    ctx.strokeStyle = "rgba(0, 0, 0, 0)";
    ctx.lineWidth = 2;
    ctx.strokeRect(metrics.x - 8, metrics.y - 8, metrics.width + 16, metrics.height + 16);
}

function setCanvasStateFromInputs() {
    title = titleInput.value;
    date = dateInput.value;
    time = timeInput.value;
    room = roomInput.value;
    presenter = presenterInput.value;
    descriptionText = descriptionInput.value;
}

templateSelect.addEventListener("change", function () {
    const selected = this.selectedOptions[0];
    const imagePath = selected?.dataset?.image || "";
    const description = selected?.dataset?.description || "";
    if (!imagePath) {
        backgroundImage = null;
        drawCanvas();
        return;
    }
    descriptionInput.value = description;
    setCanvasStateFromInputs();
    let normalizedPath = "../" +  imagePath;

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
});

titleInput.addEventListener("input", () => { setCanvasStateFromInputs(); drawCanvas(); });
dateInput.addEventListener("input", () => { setCanvasStateFromInputs(); drawCanvas(); });
timeInput.addEventListener("input", () => { setCanvasStateFromInputs(); drawCanvas(); });
roomInput.addEventListener("input", () => { setCanvasStateFromInputs(); drawCanvas(); });
presenterInput.addEventListener("input", () => { setCanvasStateFromInputs(); drawCanvas(); });
descriptionInput.addEventListener("input", () => { setCanvasStateFromInputs(); drawCanvas(); });
colorInput.addEventListener("input", drawCanvas);
sizeInput.addEventListener("input", drawCanvas);
fontInput.addEventListener("change", drawCanvas);

function isOverTextArea(x, y) {
    const metrics = getTextBlockMetrics();
    const padding = 10;
    return x >= metrics.x - padding && x <= metrics.x + metrics.width + padding &&
        y >= metrics.y - padding && y <= metrics.y + metrics.height + padding;
}

function isOverDescription(x, y) {
    ctx.font = sizeInput.value + "px " + fontInput.value;

    var width = ctx.measureText(descriptionText).width;
    var height = parseInt(sizeInput.value, 10);

    return (
        x >= descriptionX &&
        x <= descriptionX + width &&
        y >= descriptionY &&
        y <= descriptionY + height
    );
}

function clientToCanvasCoords(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    const x = (e.clientX - rect.left) * scaleX;
    const y = (e.clientY - rect.top) * scaleY;
    return { x, y };
}

canvas.addEventListener("pointerdown", function (e) {
    const p = clientToCanvasCoords(e);
    if (isOverTextArea(p.x, p.y)) {
        isDragging = true;
        dragOffset.x = p.x - textX;
        dragOffset.y = p.y - textY;
        canvas.setPointerCapture(e.pointerId);
        canvas.style.cursor = "grabbing";
    }
    if (isOverDescription(p.x, p.y)) {
        isDraggingDescription = true;
        dragOffset.x = p.x - descriptionX;
        dragOffset.y = p.y - descriptionY;
        canvas.setPointerCapture(e.pointerId);
        canvas.style.cursor = "grabbing";
    }
});

canvas.addEventListener("pointermove", function (e) {
    const p = clientToCanvasCoords(e);

    if (isDragging) {
        const metrics = getTextBlockMetrics();
        textX = p.x - dragOffset.x;
        textY = p.y - dragOffset.y;
        textX = Math.max(0, Math.min(textX, canvas.width - metrics.width));
        textY = Math.max(0, Math.min(textY, canvas.height - metrics.height));
        drawCanvas();
        return;
    }
    if (isDraggingDescription) {
        const width = ctx.measureText(descriptionText).width;
        const height = parseInt(sizeInput.value, 10);

        descriptionX = p.x - dragOffset.x;
        descriptionY = p.y - dragOffset.y;
        descriptionX = Math.max(0, Math.min(descriptionX, canvas.width - width));
        descriptionY = Math.max(0, Math.min(descriptionY, canvas.height - height));
        drawCanvas();
        return;
    }

    if (isOverDescription(p.x, p.y) || isOverTextArea(p.x, p.y)) {
        canvas.style.cursor = "grab";
    } else {
        canvas.style.cursor = "default";
    }
});

canvas.addEventListener("pointerup", function (e) {
    if (isDragging) {
        isDragging = false;
        canvas.releasePointerCapture(e.pointerId);
        canvas.style.cursor = "default";
    }
    if (isDraggingDescription) {
        isDraggingDescription = false;
        canvas.releasePointerCapture(e.pointerId);
        canvas.style.cursor = "default";
    }
});

canvas.addEventListener("pointerleave", () => {
    if (isDragging) {
        isDragging = false;
    }
    if (isDraggingDescription) {
        isDraggingDescription = false;
    }
    canvas.style.cursor = "default";
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

setCanvasStateFromInputs();
drawCanvas();

// If a template is already selected on page load, trigger change to load it
if (templateSelect && templateSelect.value) {
    templateSelect.dispatchEvent(new Event('change'));
}

