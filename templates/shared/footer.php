  </div><!-- /.page -->
</div><!-- /.main-wrap -->
</div><!-- /.app-shell -->
<script>
// DUR Notice
if(window.SHOW_DUR_NOTICE){
  var n=document.getElementById('durNotice');
  if(n){n.style.display='block';setTimeout(function(){n.style.display='none';},5000);}
}
// Podgląd koloru statusu
var nc=document.getElementById('nsColor'),np=document.getElementById('nsPreview');
if(nc&&np){nc.addEventListener('input',function(){np.style.background=this.value;np.textContent=this.value;});}
// Podgląd koloru kategorii
var kk=document.getElementById('katKolor'),kp=document.getElementById('katKolorPrev');
if(kk&&kp){kk.addEventListener('input',function(){kp.style.background=this.value;});}
// Filtr słownika po kategorii
var pubCat=document.getElementById('pubCat'),pubDict=document.getElementById('pubDict');
if(pubCat&&pubDict){
  pubCat.addEventListener('change',function(){
    var cat=this.value;
    pubDict.querySelectorAll('option[data-cat]').forEach(function(o){o.hidden=cat?o.dataset.cat!==cat:false;});
    pubDict.value='';
    var dw=document.getElementById('dupWarn');if(dw)dw.style.display='none';
  });
}
</script>
</body>
</html>
