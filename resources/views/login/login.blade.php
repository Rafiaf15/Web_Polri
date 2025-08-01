@extends('loginPage')
@section('login')

    <link rel="stylesheet" href="/css/login.css">

    <section class="background-radial-gradient overflow-hidden min-vh-100 d-flex align-items-center justify-content-center position-relative">
        <div id="radius-shape-1"></div>
        <div id="radius-shape-2"></div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                function createShootingStar() {
                    let star = document.createElement("div");
                    star.classList.add("shooting-star");
                    star.style.left = Math.random() * 100 + "vw";
                    star.style.top = Math.random() * -10 + "vh";
                    star.style.animationDuration = (Math.random() * 2 + 1.5) + "s";
                    document.body.appendChild(star);
                    setTimeout(() => star.remove(), 2000);
                }
                setInterval(createShootingStar, 700);
            });
        </script>

        <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="row w-100 justify-content-center">
                <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                    <div class="card bg-glass" style="border-radius: 1rem;">
                        <div class="card-body p-4 p-lg-5 text-black">
                            <form method="POST" action="{{ route('login.post') }}">
                                @csrf
                                <div class="d-flex align-items-center mb-3 pb-1 justify-content-center">
                                    <img src="/img/logo.png" alt="Logo" class="me-3" style="height: 50px;">
                                </div>
                                <h5 class="fw-normal mb-3 pb-3 text-center" style="letter-spacing: 1px;">{{ __('messages.login_title') }}
                                </h5>
                                @if (session('error'))
                                    <div class="alert alert-danger" role="alert">
                                        {{ session('error') }}
                                    </div>
                                @endif
                                <div class="form-outline mb-4">
                                    <label class="form-label fw-bold" for="username">{{ __('messages.username') }}</label>
                                    <input type="text" id="username" class="form-control form-control-lg"
                                        name="username" required placeholder="{{ __('messages.username_placeholder') }}" />
                                </div>
                                <div class="form-outline mb-4">
                                    <label class="form-label fw-bold" for="password">{{ __('messages.password') }}</label>
                                    <input type="password" id="password" class="form-control form-control-lg"
                                        name="password" required placeholder="{{ __('messages.password_placeholder') }}" />
                                </div>
                                <div class="pt-1 mb-4">
                                    <button class="btn btn-dark btn-lg btn-block w-100" type="submit">{{ __('messages.login_button') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection