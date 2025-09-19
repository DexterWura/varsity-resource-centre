<?php include __DIR__ . '/includes/header.php'; ?>

    <h1 class="h4 mb-3">Resume Creator</h1>
    <p class="text-muted">Enter your details, preview multiple designs, then download your resume as PDF.</p>

    <form id="resume-form" class="bg-white p-4 shadow-sm rounded-3 mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullName" placeholder="Jane Doe" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Professional Title</label>
                <input type="text" class="form-control" id="title" placeholder="Software Engineering Student">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" id="email" placeholder="jane@example.com">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" id="phone" placeholder="+263 71 234 5678">
            </div>
            <div class="col-12">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" id="location" placeholder="Harare, Zimbabwe">
            </div>
            <div class="col-12">
                <label class="form-label">Summary</label>
                <textarea class="form-control" id="summary" rows="3" placeholder="Brief profile summary"></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Skills (comma-separated)</label>
                <input type="text" class="form-control" id="skills" placeholder="Java, Python, SQL, Teamwork">
            </div>
            <div class="col-md-6">
                <label class="form-label">Education</label>
                <textarea class="form-control" id="education" rows="5" placeholder="BSc Computer Science, Midlands State University (2022 - present)
A-Level: Maths, Physics, Computer Science"></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Experience / Projects</label>
                <textarea class="form-control" id="experience" rows="5" placeholder="Intern - Software Developer, ACME (June 2024 - Aug 2024)
Built a student portal with PHP and MySQL"></textarea>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="button" id="previewBtn" class="btn btn-primary">Preview</button>
            <button type="button" id="downloadBtn" class="btn btn-success" disabled>Download PDF</button>
        </div>
    </form>

    <div class="mb-2">
        <label class="form-label">Choose Design</label>
        <div class="d-flex gap-3 align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="design" id="design1" value="template-a" checked>
                <label class="form-check-label" for="design1">Clean</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="design" id="design2" value="template-b">
                <label class="form-check-label" for="design2">Sidebar</label>
            </div>
        </div>
    </div>

    <div id="preview" class="bg-white p-4 shadow-sm rounded-3 border" style="min-height: 400px;"></div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-YcsIPa4Qm0N3QJxVv5cQ+oO8sP2z0n8YgV8E6t1oY6r4c7cL8b0SgVYb5q0M3m1x0vQv1o2pFq6t6Qd1G2mxUg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
(function(){
  const byId = (id) => document.getElementById(id);
  const formIds = ['fullName','title','email','phone','location','summary','skills','education','experience'];
  function getData(){
    const d = {};
    formIds.forEach(i => d[i] = (byId(i).value || '').trim());
    d.skillsList = d.skills ? d.skills.split(',').map(s => s.trim()).filter(Boolean) : [];
    return d;
  }

  function renderTemplateA(d){
    return `
      <div style="font-family: Arial, sans-serif; color:#212529;">
        <div style="border-bottom:2px solid #0d6efd; margin-bottom:8px;">
          <h1 style="margin:0; font-size:28px;">${escapeHtml(d.fullName)}</h1>
          <div style="color:#6c757d; margin-bottom:8px;">${escapeHtml(d.title)}</div>
          <div style="font-size:12px; color:#495057;">${escapeHtml(d.email)} · ${escapeHtml(d.phone)} · ${escapeHtml(d.location)}</div>
        </div>
        ${section('Summary', escapeHtml(d.summary))}
        ${section('Skills', d.skillsList.map(s=>`<span style="display:inline-block;border:1px solid #dee2e6;border-radius:4px;padding:2px 6px;margin:2px;">${escapeHtml(s)}</span>`).join(' '))}
        ${section('Education', nl2br(escapeHtml(d.education)))}
        ${section('Experience / Projects', nl2br(escapeHtml(d.experience)))}
      </div>`;
  }

  function renderTemplateB(d){
    return `
      <div style="display:flex; font-family: Arial, sans-serif; color:#212529;">
        <aside style="width:30%; padding-right:12px; border-right:2px solid #0d6efd;">
          <h2 style="font-size:18px; margin:0 0 8px 0; color:#0d6efd;">${escapeHtml(d.fullName)}</h2>
          <div style="font-size:12px; color:#495057;">${escapeHtml(d.title)}</div>
          <div style="margin-top:8px; font-size:12px;">
            <div>${escapeHtml(d.email)}</div>
            <div>${escapeHtml(d.phone)}</div>
            <div>${escapeHtml(d.location)}</div>
          </div>
          <div style="margin-top:12px;">
            <div style="font-weight:bold; margin-bottom:4px;">Skills</div>
            <div>${d.skillsList.map(s=>`<div style=\"font-size:12px;\">• ${escapeHtml(s)}</div>`).join('')}</div>
          </div>
        </aside>
        <section style="width:70%; padding-left:12px;">
          ${section('Summary', escapeHtml(d.summary))}
          ${section('Education', nl2br(escapeHtml(d.education)))}
          ${section('Experience / Projects', nl2br(escapeHtml(d.experience)))}
        </section>
      </div>`;
  }

  function section(title, body){
    if(!body) return '';
    return `<div style="margin-bottom:10px;"><div style="font-weight:bold; color:#0d6efd; margin-bottom:4px;">${title}</div><div>${body}</div></div>`;
  }

  function nl2br(str){
    return str.replace(/\n/g, '<br>');
  }
  function escapeHtml(str){
    return str.replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]); });
  }

  function render(){
    const d = getData();
    const design = document.querySelector('input[name="design"]:checked').value;
    const html = (design === 'template-b') ? renderTemplateB(d) : renderTemplateA(d);
    const container = document.getElementById('preview');
    container.innerHTML = html;
    document.getElementById('downloadBtn').disabled = false;
  }

  function download(){
    const element = document.getElementById('preview');
    const name = (document.getElementById('fullName').value || 'resume').replace(/[^a-z0-9\-_]+/gi,'_');
    const opt = {
      margin:       10,
      filename:     name + '.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().from(element).set(opt).save();
  }

  document.getElementById('previewBtn').addEventListener('click', render);
  document.getElementById('downloadBtn').addEventListener('click', download);
  document.querySelectorAll('input[name="design"]').forEach(r => r.addEventListener('change', render));
})();
</script>


