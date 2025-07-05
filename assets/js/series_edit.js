document.addEventListener('DOMContentLoaded',()=>{
  const applyBtn=document.getElementById('bulkApply');
  const columnSel=document.getElementById('bulkColumn');
  const valueInput=document.getElementById('bulkValue');
  const modalEl=document.getElementById('confirmBulkModal');
  if(!applyBtn||!columnSel||!valueInput||!modalEl) return;
  const modal=new bootstrap.Modal(modalEl);
  const confirmInput=document.getElementById('confirmWord');
  const finalBtn=document.getElementById('confirmBulkBtn');
  if(confirmInput){
    confirmInput.addEventListener('input',()=>{
      finalBtn.disabled=confirmInput.value.trim().toUpperCase()!=='APLICAR';
    });
  }
  applyBtn.addEventListener('click',()=>{
    if(!confirm('¿Aplicar cambios a toda la tabla?')) return;
    confirmInput.value='';
    finalBtn.disabled=true;
    modal.show();
  });
  finalBtn.addEventListener('click',async()=>{
    modal.hide();
    const seriesId=document.getElementById('seriesSel').value;
    if(!seriesId) return alert('Serie no seleccionada');
    const body={column:columnSel.value,value:valueInput.value,series_id:seriesId};
    try{
      const res=await fetch('../../api/series/bulk-column.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(body)
      });
      const j=await res.json();
      if(j.success){
        fetchSeriesTools();
      }else{
        alert('Error: '+(j.error||''));
      }
    }catch(e){
      alert('Error de conexión');
    }
  });
});
