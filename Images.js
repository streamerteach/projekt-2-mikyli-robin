/* Images.js
   - Instant preview for + photo slots
   - Remove existing photos (creates hidden photo_remove[] inputs in #removeBucket)
   - Drag reorder for existing photos (and previews visually)
   - Updates hidden order input (name="photo_order") and primary badge
   Expected elements:
     #photoGrid (contains .photoSlot)
     #newPhotos (hidden <input type="file" multiple name="new_photos[]">)
     hidden input id="photoOrder" (or legacy id="photo_order"), name="photo_order"
     #removeBucket (empty div where we append hidden inputs)
*/

document.addEventListener("DOMContentLoaded", () => {
  const grid = document.getElementById("photoGrid");
  const fileInput = document.getElementById("newPhotos");
  const orderInput =
    document.getElementById("photoOrder") || document.getElementById("photo_order");
  const removeBucket = document.getElementById("removeBucket");
  const form = document.getElementById("settingsForm");

  if (!grid || !fileInput) {
    console.warn("Images.js: Missing #photoGrid or #newPhotos.");
    return;
  }

  fileInput.setAttribute("multiple", "multiple");

  const getSlots = () => Array.from(grid.querySelectorAll(".photoSlot"));

  const getFilledSlots = () =>
    getSlots().filter((s) => !s.classList.contains("empty"));

  const getExistingFilledSlots = () =>
    getSlots().filter((s) => {
      if (s.classList.contains("empty")) return false;
      const p = (s.dataset.path || "").trim();
      return p.length > 0; // existing = has server path
    });

  const updateOrderHidden = () => {
    if (!orderInput) return;
    const paths = getExistingFilledSlots()
      .map((s) => (s.dataset.path || "").trim())
      .filter(Boolean);
    orderInput.value = paths.join(",");
  };

  const setPrimaryBadges = () => {
    const filled = getFilledSlots();

    filled.forEach((slot, idx) => {
      // keep your existing .badge element if present; otherwise create it
      let badge = slot.querySelector(".badge");
      if (!badge && !slot.classList.contains("empty")) {
        badge = document.createElement("span");
        badge.className = "badge";
        slot.appendChild(badge);
      }

      const isPrimary = idx === 0 && !slot.classList.contains("empty");
      slot.classList.toggle("primary", isPrimary);

      if (badge) {
        badge.textContent = isPrimary ? "Primary" : "Active";
      }
    });
  };

  const setSlotToEmpty = (slot) => {
    slot.classList.remove("filled", "preview", "primary");
    slot.classList.add("empty");
    slot.dataset.path = "";
    slot.setAttribute("draggable", "false");
    slot.innerHTML = "<span>+</span>";
  };

  const ensureRemoveBtn = (slot) => {
    if (slot.querySelector(".removeBtn")) return;
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "removeBtn";
    btn.title = "Remove";
    btn.textContent = "×";
    slot.appendChild(btn);
  };

  const setSlotToPreview = (slot, objectUrl) => {
    // Convert an empty slot to preview (not yet saved; data-path stays empty)
    slot.classList.remove("empty");
    slot.classList.add("filled", "preview");
    slot.setAttribute("draggable", "true");

    // Replace content cleanly but keep visuals consistent: img + removeBtn + badge
    slot.innerHTML = "";
    const img = document.createElement("img");
    img.src = objectUrl;
    img.alt = "New photo preview";
    img.draggable = false;
    slot.appendChild(img);

    ensureRemoveBtn(slot);

    const badge = document.createElement("span");
    badge.className = "badge";
    badge.textContent = "New";
    slot.appendChild(badge);

    slot.dataset.path = ""; // server path exists only after Save
  };

  // ========= Click handling =========
  grid.addEventListener("click", (e) => {
    const slot = e.target.closest(".photoSlot");
    if (!slot) return;

    // Remove click
    if (e.target.classList.contains("removeBtn")) {
      const path = (slot.dataset.path || "").trim();

      // If it's an existing stored photo, mark for removal
      if (path && removeBucket) {
        const inp = document.createElement("input");
        inp.type = "hidden";
        inp.name = "photo_remove[]";
        inp.value = path;
        removeBucket.appendChild(inp);
      }

      setSlotToEmpty(slot);
      updateOrderHidden();
      setPrimaryBadges();
      return;
    }

    // Click empty slot -> open picker
    if (slot.classList.contains("empty")) {
      fileInput.click();
    }
  });

  // ========= File selection => instant previews =========
  fileInput.addEventListener("change", () => {
    const files = Array.from(fileInput.files || []);
    if (!files.length) return;

    const emptySlots = getSlots().filter((s) => s.classList.contains("empty"));
    if (!emptySlots.length) return;

    files.forEach((file, idx) => {
      const slot = emptySlots[idx];
      if (!slot) return;
      const url = URL.createObjectURL(file);
      setSlotToPreview(slot, url);
    });

    // Order input only tracks existing (server) paths; keep it updated
    updateOrderHidden();
    setPrimaryBadges();
  });

  // ========= Drag reorder =========
  let dragEl = null;

  const onDragStart = (e) => {
    const slot = e.target.closest(".photoSlot");
    if (!slot || slot.classList.contains("empty")) return;
    dragEl = slot;
    slot.classList.add("ghost");
    e.dataTransfer.effectAllowed = "move";
  };

  const onDragEnd = () => {
    if (dragEl) dragEl.classList.remove("ghost");
    dragEl = null;
    updateOrderHidden();
    setPrimaryBadges();
  };

  const onDragOver = (e) => {
    if (!dragEl) return;
    e.preventDefault();

    const over = e.target.closest(".photoSlot");
    if (!over || over === dragEl) return;

    const slots = getSlots();
    const dragIndex = slots.indexOf(dragEl);
    const overIndex = slots.indexOf(over);
    if (dragIndex < 0 || overIndex < 0) return;

    if (dragIndex < overIndex) over.after(dragEl);
    else over.before(dragEl);
  };

  const enableDragging = () => {
    getSlots().forEach((slot) => {
      const isDraggable = !slot.classList.contains("empty");
      slot.setAttribute("draggable", isDraggable ? "true" : "false");

      slot.removeEventListener("dragstart", onDragStart);
      slot.removeEventListener("dragend", onDragEnd);

      if (isDraggable) {
        slot.addEventListener("dragstart", onDragStart);
        slot.addEventListener("dragend", onDragEnd);
      }
    });

    grid.removeEventListener("dragover", onDragOver);
    grid.addEventListener("dragover", onDragOver);
  };

  if (form) {
    form.addEventListener("submit", () => {
      updateOrderHidden();
    });
  }

  // Init
  enableDragging();
  updateOrderHidden();
  setPrimaryBadges();
});
