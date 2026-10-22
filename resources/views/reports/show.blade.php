<x-layouts.app>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-center text-gray-800">نمایش گزارش</h2>
    </x-slot>

    <div class="flex flex-col items-center justify-center px-4 py-8 space-y-12" dir="rtl">
        @php $user = Auth::user(); @endphp

        <div class="w-full max-w-3xl bg-white shadow-lg rounded-2xl p-6 space-y-6 border-t-4 border-green-500">
            <h3 class="text-xl font-bold text-gray-700 border-b pb-2">جزئیات گزارش</h3>
            @include('reports.review-history')

            <p class="text-center text-gray-600 mt-1">
                <span class="font-semibold">
                    {{ $report->author_name
                        ?? optional($report->user)->name
                        ?? optional($user)->name
                        ?? 'نامشخص' }}
                </span>
            </p>

            <div class="space-y-3">
                <div>
                    <h4 class="text-gray-600 font-semibold">عنوان:</h4>
                    <p class="text-gray-800">{{ $report->title }}</p>
                </div>

                <div>
                    <h4 class="text-gray-600 font-semibold">توضیحات:</h4>
                    <p class="text-gray-800 whitespace-pre-line">{{ $report->description }}</p>
                </div>
                @php
   
    $user = Auth::user();
@endphp

               @if($user->hasRole('Marketer')) 
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="flex items-center gap-2">
        <h4 class="text-gray-600 font-semibold">📞 تماس‌های موفق:</h4>
        <span class="text-green-600 font-bold text-lg">
            {{ $report->successful_calls ?? 0 }}
        </span>
    </div>

    <div class="flex items-center gap-2">
        <h4 class="text-gray-600 font-semibold">❌ تماس‌های ناموفق:</h4>
        <span class="text-red-600 font-bold text-lg">
            {{ $report->unsuccessful_calls ?? 0 }}
        </span>
    </div>
</div>
@endif

                <div>
                    <h4 class="text-gray-600 font-semibold">ارسال‌شده در:</h4>
                    <p class="text-gray-800">
                        {{ \Morilog\Jalali\Jalalian::fromDateTime($report->submitted_at)->format('Y-m-d H:i') }}
                    </p>
                </div>

                <div>
                    <h4 class="text-gray-600 font-semibold">وضعیت:</h4>
                    <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 rounded-full">
                        @if($report->status == "submitted")
                            <span class="badge bg-warning text-dark">خوانده نشده</span>
                        @elseif($report->status == "read")
                            <span class="badge bg-success">خوانده شده</span>
                        @endif
                    </span>
                </div>

                @if($report->attachments->count())
                    <div class="mb-3">
                        <h5 class="fw-bold text-right pb-3">فایل‌ها و تصاویر:</h5>
                        <ul class="list-unstyled text-end">
                            @foreach($report->attachments as $attachment)
                                <li class="mb-2">
                                    @if(Str::startsWith($attachment->type, 'image'))
                                        <img src="{{ Storage::url($attachment->file_path) }}"
                                             alt="تصویر گزارش"
                                             class="img-fluid mb-1 clickable-image"
                                             style="max-height: 200px; cursor: pointer;"
                                             data-bs-toggle="modal"
                                             data-bs-target="#imageModal"
                                             data-src="{{ Storage::url($attachment->file_path) }}">
                                    @else
                                        <a href="{{ Storage::url($attachment->file_path) }}" target="_blank" class="text-blue-600 underline">دانلود فایل</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @if(auth()->user()->can('feedback', $report))
                <form action="{{ route('user.reports.feedback', [$report]) }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="text-right">
                        <label for="feedback" class="block text-gray-700 font-medium mb-1">بازخورد:</label>
                        <textarea name="feedback" id="feedback" rows="4"
                                  class="w-full border rounded-lg p-3 border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-300 text-right"
                                  dir="rtl">{{ old('feedback', $report->feedback ?? '') }}</textarea>
                    </div>

                    {{-- ارسال ویس --}}
                    <div class="text-right" x-data>
                        <label class="block text-gray-700 font-medium mb-1">ارسال ویس:</label>

                        <div class="flex items-center gap-3">
                            <button type="button" id="btnToggleRec"
                                    class="w-12 h-12 bg-red-600 text-white rounded-full flex items-center justify-center text-2xl font-bold"
                                    aria-pressed="false">🎤</button>
                            <span id="recTimer" class="text-gray-700">00:00</span>
                            <button type="button" id="btnPlay" class="px-3 py-1 bg-gray-200 rounded disabled:opacity-40" disabled>پخش</button>
                            <button type="button" id="btnClear" class="px-3 py-1 bg-gray-200 rounded disabled:opacity-40" disabled>حذف</button>
                        </div>

                        {{-- فالبک انتخاب فایل (برای iOS: باز شدن ضبط صدا به‌جای دوربین) --}}
                        <div id="fallbackWrap" class="mt-3 hidden">
                            <input type="file"
                                   name="voice_file"
                                   id="voice_file"
                                   class="block w-full border rounded p-2">
                            <p class="text-xs text-gray-500 mt-1">
                                اگر ضبط مستقیم پشتیبانی نشد، از این ورودی استفاده کنید.
                            </p>
                        </div>

                        <audio id="recAudio" controls class="w-full mt-3 hidden" playsinline></audio>

                        <input type="hidden" name="voice" id="voiceInput">
                        <input type="hidden" name="voice_mime" id="voiceMime">

                        <p id="recError" class="text-red-600 text-sm mt-2 hidden"></p>

                        @if($report->voice_path ?? false)
                            <p class="mt-3 text-sm text-gray-600">ویس قبلی:</p>
                            <audio controls class="w-full" playsinline>
                                <source src="{{ Storage::url($report->voice_path) }}">
                            </audio>
                        @endif
                    </div>

                    <div class="text-center gap-4">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
                            ذخیره بازخورد
                        </button>
                    </div>
                </form>
            @else
                @if($report->feedback)
                    <div class="text-right p-3 border rounded-lg bg-gray-100">
                        {{ $report->feedback }}
                    </div>
                @else
                    <div class="text-right text-gray-500">بازخوردی ثبت نشده است.</div>
                @endif

                @if($report->voice_path ?? false)
                    <div class="mt-3">
                        <p class="text-sm text-gray-600">ویس:</p>
                        <audio controls class="w-full" playsinline>
                            <source src="{{ Storage::url($report->voice_path) }}">
                        </audio>
                    </div>
                @endif
            @endif

            <div class="flex justify-start space-x-reverse space-x-2">
                <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300">بازگشت</a>
            </div>
        </div>
    </div>

    <!-- Modal نمایش تصویر -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <img src="" id="modalImage" class="img-fluid w-100" alt="تصویر گزارش">
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

<script>
  // بزرگ‌نمایی تصویر (بدون تغییر)
  document.querySelectorAll('.clickable-image').forEach(img => {
    img.addEventListener('click', function(){
      document.getElementById('modalImage').src = this.dataset.src;
    });
  });

  // ---- Recorder + Diagnoser: MediaRecorder -> WebAudio WAV -> File Fallback ----
  (function(){
    const btn        = document.getElementById('btnToggleRec');
    if (!btn) return;
    const timerEl    = document.getElementById('recTimer');
    const playBtn    = document.getElementById('btnPlay');
    const clearBtn   = document.getElementById('btnClear');
    const audioEl    = document.getElementById('recAudio');
    const voiceInput = document.getElementById('voiceInput');
    const voiceMime  = document.getElementById('voiceMime');
    const errEl      = document.getElementById('recError');
    const fallback   = document.getElementById('fallbackWrap');
    const fileInput  = document.getElementById('voice_file');

    // ــــــ تشخیص محیط و آماده‌سازی فالبک فایل
    const ua = navigator.userAgent || '';
    const isIOS = /iP(hone|ad|od)/.test(navigator.platform) || /iOS|iPadOS/.test(ua);
    const isAndroid = /Android/i.test(ua);
    if (fallback) fallback.classList.add('hidden');
    if (fileInput) {
      if (isIOS) { fileInput.removeAttribute('capture'); fileInput.setAttribute('accept','audio/*;capture=microphone'); }
      else if (isAndroid) { fileInput.setAttribute('accept','audio/*'); fileInput.setAttribute('capture','microphone'); }
      else { fileInput.setAttribute('accept','audio/*'); fileInput.removeAttribute('capture'); }
    }

    // ــــــ کمکی‌ها
    function fmt(s){ const m=String(Math.floor(s/60)).padStart(2,'0'); const ss=String(s%60).padStart(2,'0'); return `${m}:${ss}`; }
    let tick, startTime=0;
    function setRecUI(on){
      if (on){
        btn.dataset.state='rec'; btn.setAttribute('aria-pressed','true');
        btn.classList.remove('bg-red-600'); btn.classList.add('bg-green-600');
        startTime=Date.now(); tick=setInterval(()=>{ timerEl.textContent=fmt(Math.floor((Date.now()-startTime)/1000)); },500);
      } else {
        clearInterval(tick); timerEl.textContent='00:00';
        btn.dataset.state='idle'; btn.setAttribute('aria-pressed','false');
        btn.classList.remove('bg-green-600'); btn.classList.add('bg-red-600');
      }
    }
    function showErr(msg){
      if (!msg) return;
      errEl.textContent = msg;
      errEl.classList.remove('hidden');
    }
    function showFileFallback(msg){
      if (fallback) fallback.classList.remove('hidden');
      btn.disabled = true; btn.classList.add('opacity-50');
      showErr(msg || 'ضبط مستقیم پشتیبانی نشد — از «انتخاب فایل» استفاده کنید.');
    }

    // ــــــ عیب‌یاب فوری قبل از ضبط
    (function preflight(){
      const problems = [];
      if (!window.isSecureContext) problems.push('صفحه روی HTTPS نیست (SecureContext=false).');
      if (window.top !== window.self) problems.push('صفحه داخل iframe است—نیاز به allow="microphone" روی iframe.');
      if (!navigator.mediaDevices?.getUserMedia) problems.push('getUserMedia در این مرورگر در دسترس نیست.');
      if (problems.length){
        showErr('پیش‌نیازها کامل نیست:\n- ' + problems.join('\n- '));
      }
    })();

    // ــــــ کلاس WAV فالبک (WebAudio)
    class WavRecorder {
      constructor(){ this.ctx=null; this.stream=null; this.proc=null; this.buffers=[]; this.length=0; this.sampleRate=44100; }
      async start(){
        this.stream = await navigator.mediaDevices.getUserMedia({ audio:{ echoCancellation:true, noiseSuppression:true, channelCount:1 }});
        this.ctx = new (window.AudioContext||window.webkitAudioContext)();
        await this.ctx.resume().catch(()=>{});
        this.sampleRate = this.ctx.sampleRate;
        const src = this.ctx.createMediaStreamSource(this.stream);
        const size=4096;
        this.proc = (this.ctx.createScriptProcessor||this.ctx.createJavaScriptNode).call(this.ctx,size,1,1);
        src.connect(this.proc); this.proc.connect(this.ctx.destination);
        this.proc.onaudioprocess = (e)=>{ const ch=e.inputBuffer.getChannelData(0); this.buffers.push(new Float32Array(ch)); this.length+=ch.length; };
      }
      stop(){
        if (this.proc) this.proc.disconnect();
        try{ this.stream?.getTracks()?.forEach(t=>t.stop()); }catch{}
        return this.encodeWAV();
      }
      encodeWAV(){
        const data=new Float32Array(this.length); let off=0;
        for (const b of this.buffers){ data.set(b,off); off+=b.length; }
        const dv=new DataView(new ArrayBuffer(44+data.length*2));
        const ws=(o,s)=>{ for(let i=0;i<s.length;i++) dv.setUint8(o+i,s.charCodeAt(i)); };
        const f16=(o,arr)=>{ let p=o; for (let i=0;i<arr.length;i++,p+=2){ let s=Math.max(-1,Math.min(1,arr[i])); s=s<0?s*0x8000:s*0x7FFF; dv.setInt16(p,s,true); } };
        const ch=1, sr=this.sampleRate, bps=2;
        ws(0,'RIFF'); dv.setUint32(4,36+data.length*bps,true);
        ws(8,'WAVE'); ws(12,'fmt '); dv.setUint32(16,16,true); dv.setUint16(20,1,true);
        dv.setUint16(22,ch,true); dv.setUint32(24,sr,true); dv.setUint32(28,sr*ch*bps,true);
        dv.setUint16(32,ch*bps,true); dv.setUint16(34,8*bps,true); ws(36,'data'); dv.setUint32(40,data.length*bps,true);
        f16(44,data);
        return new Blob([dv.buffer],{type:'audio/wav'});
      }
    }

    // ــــــ ضبط: MediaRecorder → WebAudio → File
    let mr=null, chunks=[], gum=null, usingWav=false, wav=null;

    const canMR = 'MediaRecorder' in window;
    const preferTypes = ['audio/mp4','audio/aac','audio/mpeg','audio/webm;codecs=opus','audio/webm'];
    function pickMime(){
      if (!canMR) return '';
      if (!window.MediaRecorder?.isTypeSupported) return 'audio/mp4';
      for (const t of preferTypes){ try{ if (MediaRecorder.isTypeSupported(t)) return t; }catch{} }
      return '';
    }
    const mimeInitial = pickMime();

    async function start(){
      errEl.classList.add('hidden'); errEl.textContent='';
      try{
        // مرحله ۱: MediaRecorder
        if (canMR && navigator.mediaDevices?.getUserMedia){
          gum = await navigator.mediaDevices.getUserMedia({ audio:{ echoCancellation:true, noiseSuppression:true, channelCount:1 }});
          let opts={ audioBitsPerSecond:64000 }; if (mimeInitial) opts.mimeType=mimeInitial;
          try { mr=new MediaRecorder(gum,opts); } catch { mr=new MediaRecorder(gum); }
          chunks=[];
          mr.ondataavailable = e=>{ if (e.data && e.data.size) chunks.push(e.data); };
          mr.onstop = ()=>{
            const t = chunks[0]?.type || mimeInitial || 'audio/mp4';
            const blob = new Blob(chunks,{type:t});
            finalize(blob,t);
            try{ gum.getTracks().forEach(tr=>tr.stop()); }catch{}
          };
          mr.start(); setRecUI(true); return;
        }
        // مرحله ۲: WebAudio WAV
        usingWav=true; wav=new WavRecorder(); await wav.start(); setRecUI(true);
      } catch(e){
        // دسته‌بندی خطاها
        const name = e && (e.name || e.code) || '';
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError'){
          showErr('دسترسی میکروفون رد شد. در Safari → aA → Website Settings → Microphone را Allow کنید، یا Settings → Safari/Privacy → Microphone.');
        } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError'){
          showErr('هیچ میکروفونی پیدا نشد. لطفاً میکروفون را وصل/فعال کنید.');
        } else if (name === 'NotReadableError' || name === 'TrackStartError'){
          showErr('میکروفون توسط برنامهٔ دیگری در حال استفاده است. آن برنامه را ببندید.');
        } else if (name === 'SecurityError'){
          showErr('SecurityError: صفحه احتمالاً HTTPS نیست یا در iframe بدون مجوز است.');
        } else if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError'){
          showErr('تنظیمات محدودکنندهٔ audio جواب نمی‌دهد. دوباره امتحان می‌کنیم با حداقل قیود.');
        } else {
          showErr('عدم دسترسی به میکروفون یا عدم پشتیبانی ضبط.');
        }
        // مرحله ۳: فالبک فایل
        showFileFallback();
      }
    }

    async function stop(){
      try{
        if (mr && mr.state !== 'inactive'){ mr.stop(); }
        else if (usingWav && wav){ const b = wav.stop(); finalize(b,'audio/wav'); }
      } finally { setRecUI(false); }
    }

    function finalize(blob, mime){
      const url = URL.createObjectURL(blob);
      audioEl.src = url; audioEl.classList.remove('hidden');
      playBtn.disabled = false; clearBtn.disabled = false;
      const fr = new FileReader();
      fr.onload = ()=>{ voiceInput.value = fr.result; voiceMime.value = mime; };
      fr.readAsDataURL(blob);
    }

    btn.addEventListener('click', ()=>{ (btn.dataset.state==='rec') ? stop() : start(); });
    playBtn.addEventListener('click', ()=>{ if (audioEl.src) audioEl.play(); });
    clearBtn.addEventListener('click', ()=>{
      audioEl.pause(); audioEl.currentTime=0; audioEl.src=''; audioEl.classList.add('hidden');
      voiceInput.value=''; voiceMime.value=''; playBtn.disabled=true; clearBtn.disabled=true;
    });

    // ــــــ تست سخت‌افزار (اختیاری: کمک به عیب‌یابی UI)
    (async function deviceProbe(){
      try{
        const list = await navigator.mediaDevices?.enumerateDevices?.();
        if (list){
          const mics = list.filter(d=>d.kind==='audioinput');
          if (!mics.length) showErr('هشدار: هیچ ورودی صدایی (microphone) گزارش نشده است.');
        }
      }catch{}
    })();
  })();
</script>
