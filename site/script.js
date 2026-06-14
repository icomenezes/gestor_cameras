/* =============================================================
   CAMERASONLINE — Interaction Layer
   Recursos:
   - Audience switch (B2B / B2C) com troca dinâmica de copy
   - Spotlight nos feature cards
   - Scroll reveal
   - Contadores animados
   - Detecção de UTM/query param pra autoselecionar audiência
   ============================================================= */

const COPY = {
  academia: {
    badge: '14 DIAS GRÁTIS · SEM CARTÃO',
    h1: 'Suas câmeras viraram um <span class="accent">serviço recorrente</span> da academia.',
    lead: 'Libere acesso controlado às câmeras da sua academia para alunos e responsáveis. Gravação no DVR, clipes para download e cobrança recorrente — sem técnico, sem aplicativo de fabricante.',
    ctaPrimary: 'Começar trial de 14 dias',
    ctaSecondary: 'Ver demonstração',
    section2Eyebrow: 'o que está incluso',
    section2H2: 'Tudo que sua academia precisa <span class="accent">em um só lugar.</span>',
    finalH2: 'Pronto para transformar suas câmeras em <span class="accent">receita recorrente?</span>',
    finalLead: '14 dias grátis, sem cartão. Em uma hora você está no ar.',
    finalCta: 'Criar conta da minha academia'
  },
  responsavel: {
    badge: '14 DIAS GRÁTIS · SEM CARTÃO',
    h1: 'Acompanhe seu filho <span class="accent">em tempo real</span>, de qualquer lugar.',
    lead: 'Veja as câmeras da academia ou escolinha pelo celular, sem instalar app. Salve clipes dos momentos mais especiais e tenha tranquilidade enquanto seu filho treina.',
    ctaPrimary: 'Pedir acesso à minha academia',
    ctaSecondary: 'Ver como funciona',
    section2Eyebrow: 'o que você vai poder fazer',
    section2H2: 'Tranquilidade pra você, <span class="accent">no seu bolso.</span>',
    finalH2: 'Sua academia ainda <span class="accent">não usa CâmerasOnline?</span>',
    finalLead: 'Indique para a recepção e ganhe 30 dias grátis quando ela aderir.',
    finalCta: 'Indicar minha academia'
  }
};

const FEATURES = {
  academia: [
    { icon: 'ti-broadcast',    title: 'Streaming ao vivo no celular',     desc: 'WebRTC sem plugin. Funciona em iPhone, Android e qualquer browser moderno. Mosaico multi-câmera incluído.' },
    { icon: 'ti-history',      title: 'Playback do DVR + clipes',          desc: 'Timeline visual do dia, navegação por data e download de clipes em MP4 com quota por usuário.' },
    { icon: 'ti-credit-card',  title: 'Cobrança recorrente automática',    desc: 'PIX, boleto e cartão via Asaas. Renovação automática, suspensão por inadimplência, zero trabalho manual.' },
    { icon: 'ti-palette',      title: 'Marca da sua academia',             desc: 'White-label completo: logo, cores, favicon e domínio próprio. O sistema veste a identidade do seu negócio.' },
    { icon: 'ti-chart-bar',    title: 'Analytics e auditoria',             desc: 'Quem acessou o quê, quando, por quanto tempo. Heatmap de uso, taxa de renovação e logs completos.' },
    { icon: 'ti-shield-lock',  title: 'Segurança em camadas',              desc: 'Acesso por câmera com expiração, isolamento total por academia e sessões com heartbeat em tempo real.' }
  ],
  responsavel: [
    { icon: 'ti-device-mobile', title: 'No celular, sem instalar nada',    desc: 'Abra o link e veja a câmera. Funciona em iPhone, Android e tablet — sem app de fabricante, sem download.' },
    { icon: 'ti-clock-play',    title: 'Reveja o dia inteiro',             desc: 'Não pôde assistir ao vivo? Volte na timeline e veja a aula em 2x ou 4x até achar o momento que importa.' },
    { icon: 'ti-scissors',      title: 'Salve clipes dos momentos top',    desc: 'Bateu um gol, fez a apresentação, venceu o campeonato? Recorte e baixe pra guardar — em MP4.' },
    { icon: 'ti-lock',          title: 'Só as câmeras autorizadas',        desc: 'A academia libera apenas as câmeras dos locais onde seu filho treina. Privacidade respeitada.' },
    { icon: 'ti-bell',          title: 'Avisos no WhatsApp',               desc: 'Receba notificações quando o sistema detectar movimento ou quando seu acesso for liberado.' },
    { icon: 'ti-pig-money',     title: 'Cancele quando quiser',            desc: 'Sem fidelidade, sem multa. Pague mensal ou anual via PIX, boleto ou cartão direto pela plataforma.' }
  ]
};

const FAQ = {
  academia: [
    { q: 'Preciso trocar minhas câmeras?', a: 'Não. Funciona com qualquer DVR/NVR Intelbras ou Dahua que tenha acesso à internet (RTSP). Hikvision e câmeras genéricas IP também são suportadas via URL RTSP.' },
    { q: 'Quanto tempo leva pra subir minha academia no ar?', a: 'Menos de 1 hora. Você cria a conta, aponta o domínio, configura as câmeras e libera acesso aos clientes. O time da CâmerasOnline acompanha o setup do primeiro tenant.' },
    { q: 'Como funciona a cobrança recorrente dos alunos?', a: 'Integração com Asaas (PIX, boleto e cartão). Cada aluno paga direto pela plataforma e a assinatura é ativada/suspensa automaticamente. Você define os preços e planos.' },
    { q: 'E se a internet do meu DVR cair?', a: 'O sistema detecta câmera offline em tempo real e te notifica por e-mail e WhatsApp. Quando voltar, reconecta sozinho — sem perder histórico do DVR.' },
    { q: 'Posso ter minha marca no sistema?', a: 'Sim, white-label completo. Logo da academia, favicon, cores primárias, e-mail e WhatsApp de suporte da academia. O cliente nunca vê a marca da CâmerasOnline.' },
    { q: 'Que dados eu vejo do uso?', a: 'Câmeras mais assistidas, heatmap de horários de pico, tempo médio de sessão, taxa de renovação, logins diários e logs completos de auditoria por usuário.' }
  ],
  responsavel: [
    { q: 'Preciso instalar algum aplicativo?', a: 'Não. Você recebe um link da academia, faz login pelo navegador do celular e pronto. Funciona em iPhone, Android e qualquer browser moderno.' },
    { q: 'Como faço pra minha academia liberar o acesso?', a: 'Procure a recepção da academia e peça acesso à CâmerasOnline. Se ela ainda não usa, você pode indicar pelo botão "Indicar minha academia".' },
    { q: 'Posso ver a câmera de qualquer lugar?', a: 'Sim. De casa, do trabalho, da viagem — em qualquer lugar com internet. O acesso é seu, individual, e protegido por senha.' },
    { q: 'Quanto custa pra mim?', a: 'O valor é definido pela academia (geralmente entre R$10 e R$30 por mês). Você paga direto na plataforma via PIX, boleto ou cartão.' },
    { q: 'E a privacidade do meu filho?', a: 'Cada usuário só vê as câmeras que a academia liberou. Os acessos são auditados — a academia sabe quem viu o quê, e o sistema é isolado por academia.' },
    { q: 'Posso baixar vídeos das aulas?', a: 'Pode recortar clipes de 1, 5 ou 10 minutos e baixar em MP4. Os arquivos ficam disponíveis por 2 dias na sua biblioteca pessoal.' }
  ]
};

/* === aplica copy conforme audiência === */
function applyAudience(aud) {
  const copy = COPY[aud];
  const features = FEATURES[aud];
  const faqs = FAQ[aud];

  document.querySelectorAll('.swap').forEach(el => el.classList.add('fading'));

  setTimeout(() => {
    document.getElementById('hero-badge').textContent = copy.badge;
    document.getElementById('hero-h1').innerHTML = copy.h1;
    document.getElementById('hero-lead').textContent = copy.lead;
    document.getElementById('hero-cta-primary').lastChild.textContent = copy.ctaPrimary;
    document.getElementById('hero-cta-secondary').lastChild.textContent = copy.ctaSecondary;
    document.getElementById('section2-eyebrow').textContent = copy.section2Eyebrow;
    document.getElementById('section2-h2').innerHTML = copy.section2H2;
    document.getElementById('final-h2').innerHTML = copy.finalH2;
    document.getElementById('final-lead').textContent = copy.finalLead;
    document.getElementById('final-cta').lastChild.textContent = copy.finalCta;

    const featGrid = document.getElementById('feature-grid');
    featGrid.innerHTML = features.map(f => `
      <div class="feature">
        <div class="feature-icon"><i class="ti ${f.icon}" style="font-size:22px" aria-hidden="true"></i></div>
        <h3>${f.title}</h3>
        <p>${f.desc}</p>
      </div>
    `).join('');
    attachSpotlight();

    const faqWrap = document.getElementById('faq-wrap');
    faqWrap.innerHTML = faqs.map((f, i) => `
      <details class="faq" ${i === 0 ? 'open' : ''}>
        <summary>${f.q}</summary>
        <p>${f.a}</p>
      </details>
    `).join('');

    document.querySelectorAll('.swap').forEach(el => el.classList.remove('fading'));
  }, 250);
}

/* === spotlight tracker nos feature cards === */
function attachSpotlight() {
  document.querySelectorAll('.feature').forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      card.style.setProperty('--mx', `${x}%`);
      card.style.setProperty('--my', `${y}%`);
    });
  });
}

/* === scroll reveal === */
function setupScrollReveal() {
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));
}

/* === contador animado === */
function setupCounters() {
  const animate = (el) => {
    const target = parseFloat(el.dataset.target);
    const suffix = el.dataset.suffix || '';
    const prefix = el.dataset.prefix || '';
    if (isNaN(target)) return;
    const duration = 1400;
    const start = performance.now();
    const tick = (now) => {
      const p = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(2, -10 * p);
      const cur = Math.round(target * eased);
      el.textContent = `${prefix}${cur}${suffix}`;
      if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animate(entry.target);
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });
  document.querySelectorAll('[data-target]').forEach(el => io.observe(el));
}

/* === audience toggle === */
function setupAudienceToggle() {
  const buttons = document.querySelectorAll('.audience-btn');
  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      buttons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const aud = btn.dataset.audience;
      applyAudience(aud);

      const url = new URL(window.location);
      url.searchParams.set('audience', aud);
      window.history.replaceState({}, '', url);

      if (window.gtag) {
        window.gtag('event', 'audience_switch', { audience_type: aud });
      }
    });
  });
}

/* === detecta UTM/query param de entrada === */
function detectAudienceFromURL() {
  const params = new URLSearchParams(window.location.search);
  const aud = params.get('audience') || params.get('publico');
  if (aud === 'responsavel' || aud === 'pai' || aud === 'parent') {
    document.querySelector('.audience-btn[data-audience="responsavel"]').click();
  }
}

/* === Modal de Cadastro Trial === */
function setupSignupModal() {
  var modal     = document.getElementById('signup-modal');
  var closeBtn  = document.getElementById('signup-close');
  var form      = document.getElementById('signup-form');
  var errorEl   = document.getElementById('signup-error');
  var submitBtn = document.getElementById('signup-submit');

  // WhatsApp para "Falar com vendas" e audiência "responsavel"
  // Atualize o número abaixo com o seu WhatsApp
  var WHATSAPP = 'https://wa.me/5511921026171?text=Ol%C3%A1!%20Tenho%20interesse%20no%20C%C3%A2merasOnline.';

  var currentAudience = 'academia';
  document.querySelectorAll('.audience-btn').forEach(function (btn) {
    btn.addEventListener('click', function () { currentAudience = btn.dataset.audience; });
  });

  // Botões CTA de trial
  document.querySelectorAll('.open-signup').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (currentAudience === 'responsavel') {
        window.open(WHATSAPP, '_blank');
        return;
      }
      openModal();
    });
  });

  // Botão "Falar com vendas" (Enterprise)
  document.querySelectorAll('.btn-enterprise').forEach(function (btn) {
    btn.addEventListener('click', function () { window.open(WHATSAPP, '_blank'); });
  });

  function openModal() {
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    setTimeout(function () { document.getElementById('signup-name').focus(); }, 80);
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
    errorEl.hidden = true;
    form.reset();
  }

  closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

  // Envio do formulário
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errorEl.hidden = true;

    var name     = document.getElementById('signup-name').value.trim();
    var email    = document.getElementById('signup-email').value.trim();
    var password = document.getElementById('signup-password').value;

    if (!name || !email || !password) { return showError('Preencha todos os campos.'); }
    if (password.length < 8)          { return showError('A senha deve ter pelo menos 8 caracteres.'); }

    setLoading(true);

    fetch('/api/register', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ name: name, email: email, password: password })
    })
    .then(function (r) {
      return r.json().then(function (d) { return { ok: r.ok, data: d }; });
    })
    .then(function (res) {
      if (res.ok) {
        var d = res.data;
        location.href = '/aguardando.html'
          + '?slug='  + encodeURIComponent(d.slug)
          + '&url='   + encodeURIComponent(d.url)
          + '&login=' + encodeURIComponent(d.login);
      } else {
        setLoading(false);
        showError(res.data.error || 'Erro ao criar conta. Tente novamente.');
      }
    })
    .catch(function () {
      setLoading(false);
      showError('Sem conexão com o servidor. Verifique sua internet.');
    });
  });

  var loadingOverlay = document.getElementById('signup-loading');

  function setLoading(on) {
    loadingOverlay.classList.toggle('active', on);
    submitBtn.disabled = on;
  }

  function showError(msg) {
    errorEl.textContent = msg;
    errorEl.hidden = false;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  attachSpotlight();
  setupScrollReveal();
  setupCounters();
  setupAudienceToggle();
  detectAudienceFromURL();
  setupSignupModal();
});