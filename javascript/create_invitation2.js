const canvas = document.getElementById("inviteCanvas");
const ctx = canvas.getContext("2d");

let uploadedImage = null;
let backgroundImage = null;

// 📦 TEXT OBJECTS (drag по отделно)
const texts = [
    { id: "title", text: "Тема", x: 30, y: 40 },
    { id: "date", text: "Дата", x: 30, y: 90 },
    { id: "room", text: "Зала", x: 30, y: 140 },
    { id: "presenter", text: "Презентиращ", x: 30, y: 190 },
    { id: "description", text: "Описание", x: 30, y: 240 }
];

// Inputs
const titleInput = document.getElementById("titleInput");
const dateInput = document.getElementById("dateInput");
const timeInput = document.getElementById("timeInput");
const roomInput = document.getElementById("roomInput");
const presenterInput = document.getElementById("presenterInput");
const descriptionInput = document.getElementById("descriptionInput");
const colorInput = document.getElementById("colorInput");
const sizeInput = document.getElementById("sizeInput");
const fontInput = document.getElementById("fontInput");
const imageInput = document.getElementById("imageInput");
const templateSelect = document.getElementById("templateSelect");
const canvasData = document.getElementById("canvasData");
const inviteForm = document.getElementById("inviteForm");
const shareBtn = document.getElementById("shareBtn");

// Drag state
let activeText = null;
let offsetX = 0;
let offsetY = 0;

// -------------------- TEXT UPDATE --------------------
function updateTextsFromInputs() {
    texts[0].text = "Тема: " + titleInput.value;
    texts[1].text = "Дата: " + dateInput.value + " " + timeInput.value;
    texts[2].text = "Зала: " + roomInput.value;
    texts[3].text = "Презентиращ: " + presenterInput.value;
    texts[4].text = descriptionInput.value;
}

// -------------------- COVER IMAGE --------------------
function drawBackground(img) {
    const scale = Math.max(
        canvas.width / img.width,
        canvas.height / img.height
    );

    const w = img.width * scale;
    const h = img.height * scale;

    const x = (canvas.width - w) / 2;
    const y = (canvas.height - h) / 2;

    ctx.drawImage(img, x, y, w, h);
}

// -------------------- DRAW CANVAS --------------------
function drawCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Background
    if (uploadedImage) {
        drawBackground(uploadedImage);
    } else if (backgroundImage) {
        drawBackground(backgroundImage);
    } else {
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    // Text style
    ctx.fillStyle = colorInput.value;
    ctx.font = `${sizeInput.value}px ${fontInput.value}`;
    ctx.textBaseline = "top";

    // Draw texts
    texts.forEach(t => {
        ctx.fillText(t.text, t.x, t.y);
    });
}

// -------------------- HIT TEST --------------------
function getTextAt(x, y) {
    return texts.find(t => {
        const w = ctx.measureText(t.text).width;
        const h = parseInt(sizeInput.value);

        return (
            x >= t.x &&
            x <= t.x + w &&
            y >= t.y &&
            y <= t.y + h
        );
    });
}

// -------------------- COORDS --------------------
function getMousePos(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    return {
        x: (e.clientX - rect.left) * scaleX,
        y: (e.clientY - rect.top) * scaleY
    };
}

// -------------------- DRAG --------------------
canvas.addEventListener("pointerdown", (e) => {
    const p = getMousePos(e);
    const hit = getTextAt(p.x, p.y);

    if (hit) {
        activeText = hit;
        offsetX = p.x - hit.x;
        offsetY = p.y - hit.y;
    }
});

canvas.addEventListener("pointermove", (e) => {
    if (!activeText) return;

    const p = getMousePos(e);

    activeText.x = p.x - offsetX;
    activeText.y = p.y - offsetY;

    drawCanvas();
});

canvas.addEventListener("pointerup", () => {
    activeText = null;
});

// -------------------- INPUT EVENTS --------------------
titleInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

dateInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

timeInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

roomInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

presenterInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

descriptionInput.addEventListener("input", () => {
    updateTextsFromInputs();
    drawCanvas();
});

// -------------------- IMAGE UPLOAD --------------------
imageInput.addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const img = new Image();
    const url = URL.createObjectURL(file);

    img.src = url;

    img.onload = () => {
        uploadedImage = img;
        URL.revokeObjectURL(url);
        drawCanvas();
    };
});

// -------------------- TEMPLATE --------------------
templateSelect.addEventListener("change", function () {
    const imgPath = this.selectedOptions[0]?.dataset?.image;

    if (!imgPath) return;

    const img = new Image();
    img.src = imgPath;

    img.onload = () => {
        backgroundImage = img;
        drawCanvas();
    };
});

// -------------------- SAVE --------------------
inviteForm.addEventListener("submit", () => {
    canvasData.value = canvas.toDataURL("image/png");
});

// -------------------- SHARE --------------------
shareBtn.addEventListener("click", async () => {
    const blob = await new Promise(res => canvas.toBlob(res, "image/png"));

    const file = new File([blob], "invite.png", { type: "image/png" });

    if (navigator.canShare && navigator.canShare({ files: [file] })) {
        await navigator.share({
            title: "Покана",
            files: [file]
        });
    }
});

// INIT
updateTextsFromInputs();
drawCanvas();