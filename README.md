# Contao OpenAI Imagemeta Bundle

The purpose of this extension is to quickly and easily generate meta from image content using ChatGPT (OpenAI).

Below we have summarized a few best practices to achieve relatively good results.
Additional features are planned in the future that will make the expansion even better.

## Getting started


```
composer require cubex-hro/contao-openai-imagemeta-bundle
```

## Compability

| Contao Version | PHP Version |
|----------------|-------------|
| \>= 5.3        | ^8.2        |


## Important note

- An OpenAI developer account is required. Sign up [here](https://platform.openai.com/signup). 
- The required token is also created [there](https://platform.openai.com/account/api-keys).
- There is a fee to use the OpenAI API. An overview of OpenAI pricing can be found here: [https://openai.com/pricing](https://openai.com/pricing)
- for reducing cost activate image compression (make sure you can use GD or Imagine)

## Best practise

- define usage limit in OpenAPI API Backend to have control over costs


## How to use

- [ ] Insert token
- [ ] Choose GPT model
- [ ] Insert preferred Image-Meta prompt
- [ ] Go to files and open image settings
- [ ] Press "generate Alt-Text"
- [ ] Enjoy the magic :)

## To-Do

- [ ] do
- [ ] some
- [ ] [magic🪄](https://media.tenor.com/IOEsG9ldvhAAAAAd/mr-bean.gif)

## Support
Contao OpenAI Imagemeta Bundle is a project for the community. If you have suggestions for improvements or comments, use the issues or, best of all, make a pull request.