// Hyper Futuristic Professional Effects (lightweight)
(function(){
  'use strict';

  const isDark = () => window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

  function initButtonsRipple(){
    if(!isDark()) return;
    const buttons = document.querySelectorAll('.btn, .action-button, .dashboard-action-button');
    if(!buttons.length) return;

    if(!document.getElementById('hf-ripple-style')){
      const s = document.createElement('style');
      s.id = 'hf-ripple-style';
      s.textContent = '@keyframes hf-ripple{to{transform:scale(4);opacity:0}}';
      document.head.appendChild(s);
    }

    buttons.forEach(btn => {
      btn.addEventListener('click', (e)=>{
        const r = document.createElement('span');
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = (e.clientX || (e.touches && e.touches[0].clientX) || 0) - rect.left - size/2;
        const y = (e.clientY || (e.touches && e.touches[0].clientY) || 0) - rect.top - size/2;
        r.style.cssText = `position:absolute;width:${size}px;height:${size}px;left:${x}px;top:${y}px;`+
          'background:rgba(0,255,255,.25);border-radius:50%;transform:scale(0);animation:hf-ripple .6s linear;pointer-events:none;z-index:1;';
        btn.style.position='relative';btn.style.overflow='hidden';
        btn.appendChild(r); setTimeout(()=>r.remove(), 620);
      },{passive:true});
    });
  }

  function optimizeCarousel(){
    if(!isDark()) return;
    document.querySelectorAll('.carousel').forEach(c =>{
      c.style.touchAction='pan-y'; c.style.overflow='visible'; c.style.position='relative';
      const inner = c.querySelector('.carousel-inner');
      if(inner){ inner.style.touchAction='pan-y'; inner.style.overflow='visible'; }
      c.querySelectorAll('.carousel-item').forEach(i=>{ i.style.touchAction='pan-y'; i.style.overflow='visible'; i.style.position='relative'; });
      if(window.bootstrap && window.bootstrap.Carousel){
        const inst = window.bootstrap.Carousel.getInstance(c); if(inst) inst.dispose();
        new window.bootstrap.Carousel(c,{touch:true,interval:false,wrap:true,keyboard:true});
      }
    });
  }

  function forceThemeApplication(){
    // Force l'application du thème même si le CSS ne se charge pas immédiatement
    document.body.classList.add('dark-mode');
    document.documentElement.style.setProperty('--hf-bg-1', '#08080d');
    document.documentElement.style.setProperty('--hf-bg-2', '#101320');
    
    // Forcer le fond sur les classes problématiques
    const problematicElements = document.querySelectorAll('.modern-dashboard, .futuristic-dashboard-container, .futuristic-enabled');
    problematicElements.forEach(el => {
      el.style.background = 'radial-gradient(1200px 800px at 10% 15%, rgba(0,255,255,0.05) 0%, transparent 55%), radial-gradient(900px 700px at 85% 75%, rgba(138,43,226,0.05) 0%, transparent 60%), linear-gradient(180deg, #08080d 0%, #101320 100%)';
      el.style.color = '#eaf2ff';
      el.style.position = 'relative';
      el.style.zIndex = '1';
    });
    
    console.log('🚀 Hyper Futuristic Theme forcé sur', problematicElements.length, 'éléments problématiques');
  }

  function init(){
    if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', init); return; }
    if(isDark()) forceThemeApplication();
    initButtonsRipple();
    optimizeCarousel();
    window.addEventListener('load', ()=>{ setTimeout(()=>{ if(isDark()) forceThemeApplication(); initButtonsRipple(); optimizeCarousel(); }, 350); }, {once:true});
    let t; window.addEventListener('resize', ()=>{ clearTimeout(t); t=setTimeout(optimizeCarousel, 250); });
  }

  init();
})();


