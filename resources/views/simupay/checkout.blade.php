{{-- Page de paiement de SimuPay. Volontairement différente de EcoCom : c'est « un autre site ». --}}
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SimuPay · Paiement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f1f2f6; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .sp-card { max-width: 400px; margin: 32px auto; background: #fff; border-radius: 16px; border: 1px solid #e3e5ec; overflow: hidden; }
        .sp-head { background: #2d2a6e; color: #fff; padding: 18px 20px; }
        .sp-brand { font-weight: 800; letter-spacing: -.02em; }
        .sp-sandbox { font-size: .72rem; background: #ffd23f; color: #2d2a6e; border-radius: 6px; padding: 2px 8px; font-weight: 700; }
        .sp-amount { font-size: 2rem; font-weight: 800; }
        .btn-sp { background: #2d2a6e; color: #fff; font-weight: 700; padding: .8rem; border-radius: 12px; }
        .btn-sp:hover { background: #221f55; color: #fff; }
    </style>
</head>
<body>
<div class="sp-card">
    <div class="sp-head d-flex justify-content-between align-items-center">
        <span class="sp-brand">SimuPay</span>
        <span class="sp-sandbox">MODE TEST · aucun vrai argent</span>
    </div>
    <div class="p-4">
        <div class="text-muted small">Payer à</div>
        <div class="fw-bold">EcoCom</div>
        <div class="text-muted small mt-1">{{ $transaction->description }}</div>
        <div class="sp-amount my-3">{{ number_format($transaction->amount, 0, ',', ' ') }} {{ $transaction->currency }}</div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('simupay.checkout.confirm', $transaction) }}">
            @csrf
            <label class="form-label fw-semibold" for="phone">Numéro de téléphone</label>
            <input id="phone" name="phone" value="{{ old('phone', '+269 ') }}" class="form-control mb-3" required>

            <label class="form-label fw-semibold" for="pin">Code secret (4 chiffres)</label>
            <input id="pin" name="pin" type="password" inputmode="numeric" maxlength="4" class="form-control mb-3" placeholder="••••" required>

            <fieldset class="mb-4">
                <legend class="form-label fw-semibold fs-6">Résultat à simuler</legend>
                @foreach ($outcomes as $value => $label)
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="outcome" id="o-{{ $value }}" value="{{ $value }}" @checked(old('outcome', 'success') === $value)>
                        <label class="form-check-label" for="o-{{ $value }}">{{ $label }}</label>
                    </div>
                @endforeach
            </fieldset>

            <button class="btn btn-sp w-100">Confirmer le paiement</button>
        </form>
        <p class="text-center text-muted small mt-3 mb-0">Réf. {{ $transaction->public_id }}</p>
    </div>
</div>
</body>
</html>
