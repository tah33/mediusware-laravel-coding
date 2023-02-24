@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>

    <div class="card">
        <form action="{{ route('product.index') }}" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" value="{{ $title ?? old('title') }}"
                           placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="variant_selection" class="form-control">
                        @foreach ($variants as $variant)
                            <optgroup label="{{ $variant->title }}">
                                @foreach($variant->productVariants as $product_variant)
                                    <option value="{{ $product_variant->variant }}"
                                        {{ isset($variant) && $product_variant->variant == $variant ? 'selected' : '' }}>{{ $product_variant->variant }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from"
                               value="{{ $price_from ?? old('price_from') }}"
                               aria-label="First name" placeholder="From"
                               class="form-control">
                        <input type="text" name="price_to" aria-label="Last name"
                               value="{{ $price_to ?? old('price_to') }}" placeholder="To"
                               class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" value="{{ $date ?? old('date') }}"
                           class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach ($products as $key=> $product)
                        <tr>
                            <td width="5%">{{ $products->firstItem() + $key }}</td>
                            <td width="10%">{{ $product->title }} <br> Created at
                                : {{ \Carbon\Carbon::parse($product->created_at)->format('d-M-Y') }}</td>
                            <td width="40%">{{ $product->description }}</td>
                            <td width="40%">
                                <dl class="row variant_row mb-0" style="height: 80px; overflow: hidden">
                                    <dt class="col-sm-3 pb-0">
                                        @foreach ($product->variantPrices as $k=> $variant_price)
                                            <p style="white-space: nowrap;margin-bottom: 8px">
                                                @if ($variant_price->variantOne)
                                                    {{ @$variant_price->variantOne->variant }}
                                                @endif
                                                @if ($variant_price->variantTwo)
                                                    / {{ @$variant_price->variantTwo->variant }}
                                                @endif
                                                @if ($variant_price->variantThree)
                                                    / {{ @$variant_price->variantThree->variant }}
                                            @endif
                                        @endforeach
                                    </dt>
                                    <dd class="col-sm-9">
                                        <dl class="row mb-0">
                                            @foreach ($product->variantPrices as $k=> $variant_price)
                                                <dt class="col-sm-4 pb-0">Price
                                                    : {{ number_format($variant_price->price,1) }}</dt>
                                                <dd class="col-sm-8 pb-0">InStock
                                                    : {{ $variant_price->stock }}</dd>
                                            @endforeach
                                        </dl>
                                    </dd>
                                </dl>
                                @if(count($product->variantPrices) > 3)
                                    <button class="btn btn-sm btn-link shor_more_btn">Show
                                        more
                                    </button>
                                    <button class="btn btn-sm btn-link d-none shor_less_btn">Show
                                        less
                                    </button>
                                @endif

                            </td>
                            <td width="5%">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} out
                        of {{ $products->total() }} </p>
                </div>
                <div class="col-md-2">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection
@push('js')
    <script>
        $(document).ready(function(){
            $('#variant_selection').select2({
                theme: 'bootstrap4',
                placeholder: 'Select a Variant',
                width: '100%',
                minimumResultsForSearch: Infinity,
                dropdownAutoWidth: true
            }).val("{{ $variant ?? '' }}").trigger('change.select2');

            $(document).on('click','.shor_more_btn',function(){
                let selector = $(this).closest('tr');
                selector.find('.variant_row').addClass('h-auto');
                selector.find('.shor_more_btn').addClass('d-none');
                selector.find('.shor_less_btn').removeClass('d-none');
            });
            $(document).on('click','.shor_less_btn',function(){
                let selector = $(this).closest('tr');
                selector.find('.variant_row').removeClass('h-auto');
                selector.find('.shor_more_btn').removeClass('d-none');
                selector.find('.shor_less_btn').addClass('d-none');
            });
        });
    </script>
@endpush
