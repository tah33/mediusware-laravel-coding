<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('variantPrices.variantOne','variantPrices.variantTwo','variantPrices.variantThree')
            ->when($request->title,function($query) use ($request){
                $query->where('title','like','%'.$request->title .'%');
            })->when($request->variant,function($query) use ($request){
                $query->whereHas('variants',function($query) use ($request){
                    $query->where('variant',$request->variant);
                });
            })->when($request->price_from && !$request->price_to,function($query) use ($request){
                $query->whereHas('variantPrices',function($query) use ($request){
                    $query->where('price','>=',$request->price_from);
                });
            })->when($request->price_to && !$request->price_from,function($query) use ($request){
                $query->whereHas('variantPrices',function($query) use ($request){
                    $query->where('price','<=',$request->price_to);
                });
            })->when($request->price_to && !$request->price_from,function($query) use ($request){
                $query->whereHas('variantPrices', function ($query) use ($request) {
                    $query->whereBetween('price', [$request->price_from, $request->price_to]);
                });
            })->when($request->date,function($query) use ($request){
                $date = Carbon::parse($request->date);
                $query->whereDate('created_at',$date)->whereMonth('created_at',$date->month)->whereYear('created_at',$date->year);
            })->latest()->paginate(2);

        $data = [
            'products'      => $products,
            'variants'      => Variant::with('productVariants')->get(),
            'title'         => $request->title,
            'variant'       => $request->variant,
            'price_from'    => $request->price_from,
            'price_to'      => $request->price_to,
            'date'          => $request->date,
        ];
        return view('products.index',$data);
    }

    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'title' => 'required',
            'sku' => 'required',
        ]);
        DB::beginTransaction();
        try {

            $product = Product::create($request->all());
            $variant_1 = $variant_2 = $variant_3 = null;
            $variants = [];
            foreach ($request->product_variant as $key => $variant) {
                foreach ($variant['tags'] as $k => $tag) {
                    $variants[] = [
                        'variant' => $tag,
                        'variant_id' => $variant['option'],
                        'product_id' => $product->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (count($variants) > 0) {
                ProductVariant::insert($variants);
            }
            $images = [];
            foreach ($request->product_image as $key => $image) {
                $images[$key] = [
                    'product_id' => $product->id,
                    'file_path' => $image,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (count($images) > 0) {
                ProductImage::insert($images);
            }
            $prices = [];
            foreach ($request->product_variant_prices as $key => $product_variant_price) {
                $variants = explode('/', $product_variant_price['title']);
                foreach ($variants as $variant) {
                    $product_variant = ProductVariant::where('variant', $variant)->where('product_id', $product->id)->first();
                    if (@$product_variant->variant_id == 1) {
                        $variant_1 = $product_variant->id;
                    }
                    if (@$product_variant->variant_id == 2) {
                        $variant_2 = $product_variant->id;
                    }
                    if (@$product_variant->variant_id == 6) {
                        $variant_3 = $product_variant->id;
                    }
                }
                $prices[$key] = [
                    'product_variant_one' => $variant_1,
                    'product_variant_two' => $variant_2,
                    'product_variant_three' => $variant_3,
                    'product_id' => $product->id,
                    'stock' => $product_variant_price['stock'],
                    'price' => $product_variant_price['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (count($prices) > 0) {
                ProductVariantPrice::insert($prices);
            }
            DB::commit();
            return response()->json([
                'success' => 'Product Create Successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something Went Wrong'
            ]);
        }
    }

    public function edit(Product $product)
    {
        $data = [
            'variants' => Variant::all(),
            'product' => $product,
        ];
        return view('products.edit', $data);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'title' => 'required',
                'sku' => 'required',
            ]);
            $product = Product::find($id);
            $product->update($request->all());
            $variant_1 = $variant_2 = $variant_3 = null;
            $variants = [];

            $product->variants()->delete();

            foreach ($request->product_variant as $key => $variant) {
                foreach ($variant['tags'] as $k => $tag) {
                    $variants[] = [
                        'variant' => $tag,
                        'variant_id' => $variant['option'],
                        'product_id' => $product->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            if (count($variants) > 0) {
                ProductVariant::insert($variants);
            }
            $product->images()->delete();

            $images = [];
            foreach ($request->product_image as $key => $image) {
                $images[$key] = [
                    'product_id' => $product->id,
                    'file_path' => $image,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (count($images) > 0) {
                ProductImage::insert($images);
            }
            $product->variantPrices()->delete();

            $prices = [];
            foreach ($request->product_variant_prices as $key => $product_variant_price) {
                $variants = explode('/', $product_variant_price['title']);
                foreach ($variants as $variant) {
                    $product_variant = ProductVariant::where('variant', $variant)->where('product_id', $product->id)->first();
                    if (@$product_variant->variant_id == 1) {
                        $variant_1 = $product_variant->id;
                    }
                    if (@$product_variant->variant_id == 2) {
                        $variant_2 = $product_variant->id;
                    }
                    if (@$product_variant->variant_id == 6) {
                        $variant_3 = $product_variant->id;
                    }
                }
                $prices[$key] = [
                    'product_variant_one' => $variant_1,
                    'product_variant_two' => $variant_2,
                    'product_variant_three' => $variant_3,
                    'product_id' => $product->id,
                    'stock' => $product_variant_price['stock'],
                    'price' => $product_variant_price['price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (count($prices) > 0) {
                ProductVariantPrice::insert($prices);
            }
            DB::commit();
            return response()->json([
                'success' => 'Product Updated Successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something Went Wrong'
            ]);
        }
    }

    public function images(Request $request): string
    {
        $file = $request->file;
        $name = 'product-' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move('uploads/products/', $name);
        return 'uploads/products/' . $name;
    }

    public function unlinkImage(Request $request): bool
    {
        $request->validate([
            'file' => 'required'
        ]);
        try {
            DB::beginTransaction();
            ProductImage::where('product_id', $request->product_id)->where('file_path', 'uploads/products/' . $request->file)->delete();
            if (file_exists(base_path('public/uploads/products/' . $request->file))) {
                DB::commit();
                return unlink('uploads/products/' . $request->file);
            }
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return true;
        }
    }

    public function destroy(Product $product)
    {
        //
    }
}
